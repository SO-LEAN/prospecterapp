<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;
use Tests\App\Double\InMemoryPrivateStorage;
use Tests\App\Double\TheWeb;

/**
 * An AI plans, handles and corrects the exchanges, and notes, ticks off and erases the to-dos, as its member does on
 * the screens (ADR 0163).
 */
#[Group('api')]
class ExchangeAndToDoToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const string QUOTE_ON_THE_WEB = TheWeb::HOST.'/devis.png';

    private const array FRIDAY = ['text' => 'Envoyer le CV', 'dueOn' => '2026-10-16', 'dueAt' => '10:00'];

    public function testTheAiPlansAVideoCallWithAContact(): void
    {
        $ada = $this->ada();
        $tomorrow = ['channel' => 'videoCall', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'durationInMinutes' => 30, 'text' => 'Démonstration'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('schedule-contact-exchange', ['id' => $ada, ...$tomorrow]);

        $this->assertSame([['videoCall', '2026-10-16T08:00:00+00:00', 'planned']], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['occurredAt'], $exchange['status']], array_slice(self::recordsAt($upcoming, 'items'), 0, 1)));
    }

    public function testTheAiPlansAMeetingWithAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $ada = $this->ada();
        $meeting = ['channel' => 'meeting', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'place' => '1 place Kléber, Strasbourg'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('schedule-organization-exchange', ['id' => $babbage, 'personId' => $ada, ...$meeting]);

        $this->assertSame(['meeting', '1 place Kléber, Strasbourg'], [self::valueAt($upcoming, 'items', 0, 'exchange', 'channel'), self::valueAt($upcoming, 'items', 0, 'exchange', 'place')]);
    }

    public function testAnExchangeTheAiPlansIsCreatedByAnAi(): void
    {
        $ada = $this->ada();
        $this->login();

        $upcoming = $this->answerOf($this->callWithoutHandshake('schedule-contact-exchange', ['id' => $ada, 'channel' => 'call', 'occurredAt' => '2026-10-16T10:00:00+02:00']));

        $this->assertTrue(self::valueAt($upcoming, 'items', 0, 'createdByAnAI'));
    }

    public function testAnExchangeTheAiPlansWithAnOrganizationIsCreatedByAnAi(): void
    {
        $babbage = $this->babbage();
        $this->login();

        $upcoming = $this->answerOf($this->callWithoutHandshake('schedule-organization-exchange', ['id' => $babbage, 'channel' => 'call', 'occurredAt' => '2026-10-16T10:00:00+02:00']));

        $this->assertTrue(self::valueAt($upcoming, 'items', 0, 'exchange', 'createdByAnAI'));
    }

    public function testTheAiMarksDoneACallToProcessWithItsReport(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();
        $report = ['text' => 'Intéressée, rappeler en janvier'];

        $done = $this->assertTheToolActsAsItsEndpoint('mark-exchange-done', ['id' => $call, ...$report]);

        $this->assertSame(['done', 'Intéressée, rappeler en janvier'], [$done['status'], $done['text']]);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/exchanges-to-process'), 'items'));
    }

    public function testTheAiMarksMissedACallToProcess(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();

        $missed = $this->assertTheToolActsAsItsEndpoint('mark-exchange-missed', ['id' => $call]);

        $this->assertSame('missed', $missed['status']);
    }

    public function testTheAiMovesAPlannedExchangeOfferingToMoveItBack(): void
    {
        $call = $this->aCallWithAdaPlannedNextTuesday();
        $wednesday = ['occurredAt' => '2026-10-21T09:00:00+00:00', 'durationInMinutes' => 45];
        $this->login();

        $moved = $this->answerOf($this->callWithoutHandshake('reschedule-exchange', ['id' => $call, ...$wednesday]));

        $this->assertSame(['planned', '2026-10-21T09:00:00+00:00', 45], [$moved['status'], $moved['occurredAt'], $moved['durationInMinutes']]);
        $this->assertSame(['href' => '/api/exchanges/'.$call.'/moment', 'method' => 'PATCH'], self::valueAt($moved, '_links', 'moveBack'));
        $linksOfTheExchange = self::recordAt($moved, '_links');
        unset($linksOfTheExchange['moveBack']);
        $this->assertSame($this->requestJson('GET', '/api/exchanges/'.$call), [...$moved, '_links' => $linksOfTheExchange]);
    }

    public function testTheAiCancelsAPlannedExchange(): void
    {
        $call = $this->aCallWithAdaPlannedNextTuesday();

        $cancelled = $this->assertTheToolActsAsItsEndpoint('cancel-exchange', ['id' => $call, 'canceller' => 'contact']);

        $this->assertSame(['cancelled', 'contact'], [$cancelled['status'], $cancelled['canceller']]);
    }

    public function testTheAiErasesAnExchange(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('delete-exchange', ['id' => $call])['isError'] ?? false);

        $this->requestJson('GET', '/api/exchanges/'.$call);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheAiCorrectsTheFactsOfAnExchange(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $facts = ['channel' => 'videoCall', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T10:00:00+00:00', 'text' => 'Visio, à son initiative'];

        $corrected = $this->assertTheToolActsAsItsEndpoint('update-exchange', ['id' => $call, ...$facts]);

        $this->assertSame(['videoCall', 'inbound', 'Visio, à son initiative'], [$corrected['channel'], $corrected['direction'], $corrected['text']]);
    }

    public function testTheAiCorrectsTheTextOfAnExchangeOnly(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();

        $corrected = $this->assertTheToolActsAsItsEndpoint('set-exchange-text', ['id' => $call, 'text' => 'Appel sans suite']);

        $this->assertSame(['call', 'Appel sans suite'], [$corrected['channel'], $corrected['text']]);
    }

    public function testTheAiCorrectsWhereAMeetingIsHeld(): void
    {
        $ada = $this->ada();
        $meeting = self::stringAt($this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'meeting', 'occurredAt' => '2026-10-20T14:00:00+00:00', 'place' => 'Café de Flore']), 'items', 0, 'id');

        $corrected = $this->assertTheToolActsAsItsEndpoint('set-exchange-venue', ['id' => $meeting, 'place' => 'Les Deux Magots']);

        $this->assertSame('Les Deux Magots', $corrected['place']);
    }

    public function testTheAiPushesAgainIntoTheCalendarAtGoogleAnExchangeItRefused(): void
    {
        $this->keepTheKernel();
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheEvents();
        $call = $this->aCallWithAdaPlannedNextTuesday();
        $this->google()->acceptsTheEvents();
        $this->login();

        $sync = $this->answerOf($this->callWithoutHandshake('retry-google-sync', ['id' => $call]));

        $this->assertSame('synced', self::valueAt($sync, 'sync', 'state'));
        $this->assertSame($this->requestJson('GET', '/api/exchanges/'.$call.'/google-sync'), $sync);
        $this->assertCount(1, $this->google()->events());
    }

    public function testTheAiJoinsToAnExchangeAFileByItsAddressOnTheWeb(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(self::QUOTE_ON_THE_WEB, (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $quote = ['id' => $call, 'url' => self::QUOTE_ON_THE_WEB, 'name' => 'devis.png'];
        $this->login();

        $joined = $this->answerOf($this->callWithoutHandshake('attach-file', $quote));

        $this->assertSame([['devis.png', filesize(__DIR__.'/../Fixtures/logo.png'), 'image/png']], array_map(static fn (array $attachment): array => [$attachment['name'], $attachment['size'], $attachment['mediaType']], self::recordsAt($joined, 'attachments')));
        $this->assertSame($this->requestJson('GET', '/api/exchanges/'.$call), $joined);
    }

    public function testAFileBehindASignInIsRefusedSayingSo(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $this->keepTheKernel();
        self::service(TheWeb::class)->keepBehindASignIn(self::QUOTE_ON_THE_WEB);
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('attach-file', ['id' => $call, 'url' => self::QUOTE_ON_THE_WEB, 'name' => 'devis.png']));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertStringContainsString('behind a sign-in', self::stringAt($refusal, 'violations', 0, 'title'));
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/exchanges/'.$call), 'attachments'));
    }

    public function testAFileForbiddenToWhoeverIsNotSignedInIsRefusedSayingItIsBehindASignIn(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $this->keepTheKernel();
        self::service(TheWeb::class)->keepBehindASignIn(self::QUOTE_ON_THE_WEB, 403);
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('attach-file', ['id' => $call, 'url' => self::QUOTE_ON_THE_WEB, 'name' => 'devis.png']));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertStringContainsString('behind a sign-in', self::stringAt($refusal, 'violations', 0, 'title'));
    }

    /**
     * A private drive sends whoever is not signed in to its sign-in page.
     */
    public function testAFileWhoseAddressRedirectsIsRefusedSayingItMayBeBehindASignIn(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $this->keepTheKernel();
        self::service(TheWeb::class)->redirect(self::QUOTE_ON_THE_WEB, TheWeb::HOST.'/sign-in');
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('attach-file', ['id' => $call, 'url' => self::QUOTE_ON_THE_WEB, 'name' => 'devis.png']));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertStringContainsString('redirects', self::stringAt($refusal, 'violations', 0, 'title'));
        $this->assertStringContainsString('sign-in', self::stringAt($refusal, 'violations', 0, 'title'));
    }

    public function testTheAiRemovesAFileJoinedToAnExchange(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $upload = self::stringAt($this->requestJson('POST', '/api/uploads'), 'upload');
        self::service(InMemoryPrivateStorage::class)->send($upload, 2048, 'application/pdf');
        $attachment = self::stringAt($this->requestJson('POST', '/api/exchanges/'.$call.'/attachments', ['upload' => $upload, 'name' => 'devis.pdf']), 'attachments', 0, 'id');

        $removed = $this->assertTheToolActsAsItsEndpoint('delete-attachment', ['id' => $call, 'attachmentId' => $attachment]);

        $this->assertSame([], $removed['attachments']);
        $this->assertFalse(self::service(InMemoryPrivateStorage::class)->holds($upload));
    }

    public function testTheAiPushesAgainIntoTheCalendarAtGoogleEveryExchangeOfItsMemberItRefused(): void
    {
        $this->keepTheKernel();
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheEvents();
        $this->aCallWithAdaPlannedNextTuesday();
        $this->google()->acceptsTheEvents();

        $connections = $this->answerOf($this->callWithoutHandshake('retry-failed-google-syncs'));

        $this->assertSame(['synced' => 1, 'failed' => 0], array_intersect_key(self::arrayAt($connections, 'connections', 0), ['synced' => true, 'failed' => true]));
        $this->assertSame($this->requestJson('GET', '/api/me/connections'), $connections);
        $this->assertCount(1, $this->google()->events());
    }

    public function testTheAiNotesAToDoForAContact(): void
    {
        $ada = $this->ada();

        $noted = $this->assertTheToolActsAsItsEndpoint('create-contact-todo', ['id' => $ada, ...self::FRIDAY]);

        $this->assertSame(['Envoyer le CV', '2026-10-16', $ada], [$noted['text'], $noted['dueOn'], self::valueAt($noted, 'contact', 'id')]);
    }

    public function testTheAiNotesAToDoForAnOrganization(): void
    {
        $babbage = $this->babbage();

        $noted = $this->assertTheToolActsAsItsEndpoint('create-organization-todo', ['id' => $babbage, ...self::FRIDAY]);

        $this->assertSame(['Envoyer le CV', null], [$noted['text'], $noted['contact']]);
        $this->login();
        $this->assertContains($noted['id'], array_column(self::recordsAt($this->requestJson('GET', '/api/organizations/'.$babbage.'/to-dos'), 'toDos'), 'id'));
    }

    public function testTheAiTicksOffTheToDoItNoted(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();

        $ticked = $this->assertTheToolActsAsItsEndpoint('complete-todo', ['id' => $toDo]);

        $this->assertSame('2026-10-15T09:00:00+00:00', $ticked['doneAt']);
    }

    public function testTheAiUnticksAToDoTickedOffByMistake(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $this->callWithoutHandshake('complete-todo', ['id' => $toDo]);

        $unticked = $this->assertTheToolActsAsItsEndpoint('reopen-todo', ['id' => $toDo]);

        $this->assertNull($unticked['doneAt']);
    }

    public function testTheAiRevisesAToDo(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $monday = ['text' => 'Envoyer le CV et la proposition', 'dueOn' => '2026-10-19'];

        $revised = $this->assertTheToolActsAsItsEndpoint('update-todo', ['id' => $toDo, ...$monday]);

        $this->assertSame(['Envoyer le CV et la proposition', '2026-10-19', null], [$revised['text'], $revised['dueOn'], $revised['dueAt']]);
    }

    public function testTheAiErasesAToDo(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('delete-todo', ['id' => $toDo])['isError'] ?? false);

        $this->requestJson('GET', '/api/to-dos/'.$toDo);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * « Envoyer le CV » to Ada Lovelace on Friday, noted by the AI of the prospector.
     */
    private function aToDoForAdaNotedByTheAi(): string
    {
        $ada = $this->ada();

        return self::stringAt($this->answerOf($this->callWithoutHandshake('create-contact-todo', ['id' => $ada, ...self::FRIDAY])), 'id');
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/contacts?q=Lovelace'), 'items', 0, 'id');
    }

    /**
     * A call the prospector planned with Ada Lovelace, next Tuesday.
     */
    private function aCallWithAdaPlannedNextTuesday(): string
    {
        $ada = $this->ada();

        return self::stringAt($this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-20T14:00:00+00:00']), 'items', 0, 'id');
    }

    /**
     * A call the prospector had with Ada Lovelace yesterday, noted.
     */
    private function aCallWithAdaNotedYesterday(): string
    {
        $ada = $this->ada();
        $this->requestJson('POST', '/api/contacts/'.$ada.'/exchanges', ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-14T09:00:00+00:00', 'text' => 'Premier appel']);

        return basename((string) $this->client->getResponse()->headers->get('Location'));
    }

    /**
     * A call the prospector planned with Ada Lovelace yesterday, to process.
     */
    private function aCallWithAdaPlannedYesterday(): string
    {
        $call = Exchange::planned(new Gesture($this->accountOfTheFixtures(), new MemberId($this->memberIdOf('prospector-user')), new \DateTimeImmutable('2026-10-13 09:00 UTC')), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new ContactId($this->ada()), [new OrganizationId($this->babbage())]), ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-14 09:00 UTC'), Duration::inMinutes(null), null));
        self::service(ExchangeRepository::class)->save($call);

        return $call->id()->value;
    }

    /**
     * The calendar of the member signed in at Google connected, its consent given.
     */
    private function connectGoogle(): void
    {
        parse_str((string) parse_url(self::stringAt($this->requestJson('POST', '/api/me/connections/google/authorizations'), 'address'), \PHP_URL_QUERY), $query);
        $this->google()->grants('a-code-of-google', 'michel@exemple.fr', 'a-refresh-token-'.self::stringAt($query, 'state'));
        $this->requestJson('POST', '/api/me/connections/google', ['code' => 'a-code-of-google', 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }

    private function google(): GoogleDouble
    {
        return self::service(GoogleDouble::class);
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/organizations?q=Babbage'), 'items', 0, 'id');
    }
}
