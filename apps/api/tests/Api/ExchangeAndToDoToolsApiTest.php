<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;
use Tests\App\Double\InMemoryPrivateStorage;

/**
 * An AI plans, handles and corrects the exchanges, and notes, ticks off and erases the to-dos, as its member does on
 * the screens (ADR 0163).
 */
#[Group('api')]
class ExchangeAndToDoToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    /** The exchanges planned are handled by their holder only, the prospector here. */
    private const array HOLDER = ['prospector-user'];

    private const array FRIDAY = ['text' => 'Envoyer le CV', 'dueOn' => '2026-10-16', 'dueAt' => '10:00'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiPlansAVideoCallWithAContact(): void
    {
        $ada = $this->ada();
        $tomorrow = ['channel' => 'videoCall', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'durationInMinutes' => 30, 'text' => 'Démonstration'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('planifier-echange', ['id' => $ada, ...$tomorrow], 'POST', '/api/contacts/'.$ada.'/upcoming-exchanges', $tomorrow, self::PROSPECTORS);

        $this->assertSame([['videoCall', '2026-10-16T08:00:00+00:00', 'planned']], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['occurredAt'], $exchange['status']], array_slice($upcoming['items'], 0, 1)));
    }

    public function testTheAiPlansAMeetingWithAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $ada = $this->ada();
        $meeting = ['channel' => 'meeting', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'place' => '1 place Kléber, Strasbourg'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('planifier-echange-organisation', ['id' => $babbage, 'personId' => $ada, ...$meeting], 'POST', '/api/organizations/'.$babbage.'/upcoming-exchanges', ['exchange' => $meeting, 'personId' => $ada], self::PROSPECTORS);

        $this->assertSame(['meeting', '1 place Kléber, Strasbourg'], [$upcoming['items'][0]['exchange']['channel'], $upcoming['items'][0]['exchange']['place']]);
    }

    public function testTheAiMarksDoneACallToProcessWithItsReport(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();
        $report = ['text' => 'Intéressée, rappeler en janvier'];

        $done = $this->assertTheToolActsAsItsEndpoint('marquer-echange-fait', ['id' => $call, ...$report], 'POST', '/api/exchanges/'.$call.'/done', $report, self::HOLDER);

        $this->assertSame(['done', 'Intéressée, rappeler en janvier'], [$done['status'], $done['text']]);
        $this->assertSame([], $this->requestJson('GET', '/api/me/exchanges-to-process')['items']);
    }

    public function testTheAiMarksMissedACallToProcess(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();

        $missed = $this->assertTheToolActsAsItsEndpoint('marquer-echange-manque', ['id' => $call], 'POST', '/api/exchanges/'.$call.'/missed', null, self::HOLDER);

        $this->assertSame('missed', $missed['status']);
    }

    public function testTheAiMovesAPlannedExchangeOfferingToMoveItBack(): void
    {
        $call = $this->aCallWithAdaPlannedNextTuesday();
        $wednesday = ['occurredAt' => '2026-10-21T09:00:00+00:00', 'durationInMinutes' => 45];
        $this->assertTheToolIsRefusedAsItsEndpoint('deplacer-echange', ['id' => $call, ...$wednesday], 'PATCH', '/api/exchanges/'.$call.'/moment', $wednesday, ['assistant-user', 'admin-user', 'member-user', 'stranger']);
        $this->login();

        $moved = $this->answerOf($this->callWithoutHandshake('deplacer-echange', ['id' => $call, ...$wednesday]));

        $this->assertSame(['planned', '2026-10-21T09:00:00+00:00', 45], [$moved['status'], $moved['occurredAt'], $moved['durationInMinutes']]);
        $this->assertSame(['href' => '/api/exchanges/'.$call.'/moment', 'method' => 'PATCH'], $moved['_links']['moveBack']);
        unset($moved['_links']['moveBack']);
        $this->assertSame($this->requestJson('GET', '/api/exchanges/'.$call), $moved);
    }

    public function testTheAiCancelsAPlannedExchangeOnceItsMemberConfirmed(): void
    {
        $call = $this->aCallWithAdaPlannedNextTuesday();

        $cancelled = $this->assertTheToolActsAsItsEndpoint('annuler-echange', ['id' => $call, 'canceller' => 'contact'], 'POST', '/api/exchanges/'.$call.'/cancellation', ['canceller' => 'contact'], self::HOLDER);

        $this->assertSame(['cancelled', 'contact'], [$cancelled['status'], $cancelled['canceller']]);
        $this->assertTheToolAsksForAConfirmation('annuler-echange');
    }

    public function testTheAiErasesAnExchangeOnceItsMemberConfirmed(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();
        $this->assertTheToolIsRefusedAsItsEndpoint('effacer-echange', ['id' => $call], 'DELETE', '/api/exchanges/'.$call, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('effacer-echange', ['id' => $call])['isError'] ?? false);

        $this->requestJson('GET', '/api/exchanges/'.$call);
        $this->assertResponseStatusCodeSame(404);
        $this->assertTheToolAsksForAConfirmation('effacer-echange');
    }

    public function testTheAiCorrectsTheFactsOfAnExchange(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $facts = ['channel' => 'videoCall', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T10:00:00+00:00', 'text' => 'Visio, à son initiative'];

        $corrected = $this->assertTheToolActsAsItsEndpoint('corriger-echange', ['id' => $call, ...$facts], 'PATCH', '/api/exchanges/'.$call.'/facts', $facts, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['videoCall', 'inbound', 'Visio, à son initiative'], [$corrected['channel'], $corrected['direction'], $corrected['text']]);
    }

    public function testTheAiCorrectsTheTextOfAnExchangeOnly(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();

        $corrected = $this->assertTheToolActsAsItsEndpoint('corriger-texte-echange', ['id' => $call, 'text' => 'Appel sans suite'], 'PATCH', '/api/exchanges/'.$call.'/text', ['text' => 'Appel sans suite'], self::PROSPECTORS);

        $this->assertSame(['call', 'Appel sans suite'], [$corrected['channel'], $corrected['text']]);
    }

    public function testTheAiCorrectsWhereAMeetingIsHeld(): void
    {
        $ada = $this->ada();
        $meeting = $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'meeting', 'occurredAt' => '2026-10-20T14:00:00+00:00', 'place' => 'Café de Flore'])['items'][0]['id'];

        $corrected = $this->assertTheToolActsAsItsEndpoint('corriger-lieu-echange', ['id' => $meeting, 'place' => 'Les Deux Magots'], 'PATCH', '/api/exchanges/'.$meeting.'/venue', ['place' => 'Les Deux Magots'], self::PROSPECTORS);

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
        $this->assertTheToolIsRefusedAsItsEndpoint('relancer-synchronisation-google', ['id' => $call], 'POST', '/api/exchanges/'.$call.'/google-sync/retries', null, ['assistant-user', 'admin-user', 'member-user', 'stranger']);
        $this->login();

        $sync = $this->answerOf($this->callWithoutHandshake('relancer-synchronisation-google', ['id' => $call]));

        $this->assertSame('synced', $sync['sync']['state']);
        $this->assertSame($this->requestJson('GET', '/api/exchanges/'.$call.'/google-sync'), $sync);
        $this->assertCount(1, $this->google()->events());
    }

    public function testTheAiRemovesAFileJoinedToAnExchangeOnceItsMemberConfirmed(): void
    {
        $call = $this->aCallWithAdaNotedYesterday();
        $upload = $this->requestJson('POST', '/api/uploads')['upload'];
        self::getContainer()->get(InMemoryPrivateStorage::class)->send($upload, 2048, 'application/pdf');
        $attachment = $this->requestJson('POST', '/api/exchanges/'.$call.'/attachments', ['upload' => $upload, 'name' => 'devis.pdf'])['attachments'][0]['id'];

        $removed = $this->assertTheToolActsAsItsEndpoint('retirer-piece-jointe', ['id' => $call, 'attachmentId' => $attachment], 'DELETE', '/api/exchanges/'.$call.'/attachments/'.$attachment, null, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame([], $removed['attachments']);
        $this->assertFalse(self::getContainer()->get(InMemoryPrivateStorage::class)->holds($upload));
        $this->assertTheToolAsksForAConfirmation('retirer-piece-jointe');
    }

    public function testTheAiNotesAToDoForAContact(): void
    {
        $ada = $this->ada();

        $noted = $this->assertTheToolActsAsItsEndpoint('noter-a-faire', ['id' => $ada, ...self::FRIDAY], 'POST', '/api/contacts/'.$ada.'/to-dos', self::FRIDAY, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['Envoyer le CV', '2026-10-16', $ada], [$noted['text'], $noted['dueOn'], $noted['contact']['id']]);
    }

    public function testTheAiNotesAToDoForAnOrganization(): void
    {
        $babbage = $this->babbage();

        $noted = $this->assertTheToolActsAsItsEndpoint('noter-a-faire-organisation', ['id' => $babbage, ...self::FRIDAY], 'POST', '/api/organizations/'.$babbage.'/to-dos', self::FRIDAY, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['Envoyer le CV', null], [$noted['text'], $noted['contact']]);
        $this->login();
        $this->assertContains($noted['id'], array_column($this->requestJson('GET', '/api/organizations/'.$babbage.'/to-dos')['toDos'], 'id'));
    }

    public function testTheAiTicksOffTheToDoItNoted(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();

        $ticked = $this->assertTheToolActsAsItsEndpoint('cocher-a-faire', ['id' => $toDo], 'POST', '/api/to-dos/'.$toDo.'/done', null, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('2026-10-15T09:00:00+00:00', $ticked['doneAt']);
    }

    public function testTheAiUnticksAToDoTickedOffByMistake(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $this->callWithoutHandshake('cocher-a-faire', ['id' => $toDo]);

        $unticked = $this->assertTheToolActsAsItsEndpoint('decocher-a-faire', ['id' => $toDo], 'DELETE', '/api/to-dos/'.$toDo.'/done', null, self::PERSONAL_DATA_MANAGERS);

        $this->assertNull($unticked['doneAt']);
    }

    public function testTheAiRevisesAToDo(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $monday = ['text' => 'Envoyer le CV et la proposition', 'dueOn' => '2026-10-19'];

        $revised = $this->assertTheToolActsAsItsEndpoint('reviser-a-faire', ['id' => $toDo, ...$monday], 'PATCH', '/api/to-dos/'.$toDo, $monday, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['Envoyer le CV et la proposition', '2026-10-19', null], [$revised['text'], $revised['dueOn'], $revised['dueAt']]);
    }

    public function testTheAiErasesAToDoOnceItsMemberConfirmed(): void
    {
        $toDo = $this->aToDoForAdaNotedByTheAi();
        $this->assertTheToolIsRefusedAsItsEndpoint('effacer-a-faire', ['id' => $toDo], 'DELETE', '/api/to-dos/'.$toDo, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('effacer-a-faire', ['id' => $toDo])['isError'] ?? false);

        $this->requestJson('GET', '/api/to-dos/'.$toDo);
        $this->assertResponseStatusCodeSame(404);
        $this->assertTheToolAsksForAConfirmation('effacer-a-faire');
    }

    /**
     * « Envoyer le CV » to Ada Lovelace on Friday, noted by the AI of the prospector.
     */
    private function aToDoForAdaNotedByTheAi(): string
    {
        $ada = $this->ada();

        return $this->answerOf($this->callWithoutHandshake('noter-a-faire', ['id' => $ada, ...self::FRIDAY]))['id'];
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/contacts?q=Lovelace')['items'][0]['id'];
    }

    /**
     * A call the prospector planned with Ada Lovelace, next Tuesday.
     */
    private function aCallWithAdaPlannedNextTuesday(): string
    {
        $ada = $this->ada();

        return $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-20T14:00:00+00:00'])['items'][0]['id'];
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
        $call = Exchange::plannedWithAContact(
            $this->accountOfTheFixtures(),
            new MemberId($this->memberIdOf('prospector-user')),
            new ExchangeId(self::newIdentity()),
            new PersonId($this->ada()),
            [new OrganizationId($this->babbage())],
            ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-14 09:00 UTC'), Duration::inMinutes(null), null),
            new \DateTimeImmutable('2026-10-13 09:00 UTC'),
        );
        self::getContainer()->get(ExchangeRepository::class)->save($call);

        return $call->id()->value;
    }

    /**
     * The calendar of the member signed in at Google connected, its consent given.
     */
    private function connectGoogle(): void
    {
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);
        $this->google()->grants('a-code-of-google', 'michel@exemple.fr', 'a-refresh-token-'.$query['state']);
        $this->requestJson('POST', '/api/me/connections/google', ['code' => 'a-code-of-google', 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/organizations?q=Babbage')['items'][0]['id'];
    }
}
