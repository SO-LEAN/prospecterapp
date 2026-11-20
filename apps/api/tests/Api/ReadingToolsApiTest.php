<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;
use Tests\App\Double\InMemoryPrivateStorage;
use Tests\App\Double\OutsideServices;

/**
 * An AI reads Prospecter as its member reads the screens (ADR 0163): each tool of reading answers what its endpoint
 * answers, the resource and its links, or its refusal, to a prospector, an assistant without the right to manage the
 * personal data, an administrator, a member who is no prospector and a member of another account alike.
 */
#[Group('api')]
class ReadingToolsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array BABBAGE_COMPANY = [
        'siren' => '552100554',
        'nom_complet' => 'BABBAGE & CO',
        'matching_etablissements' => [['siret' => '55210055400021', 'adresse' => '2 RUE DES CLEFS 68000 COLMAR', 'commune' => '68066', 'activite_principale' => '62.02A', 'etat_administratif' => 'A']],
    ];

    private const array READERS = ['prospector-user', 'assistant-user', 'admin-user', 'member-user', 'stranger'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiSearchesTheOrganizationsAsTheScreenOfTheSearch(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('rechercher-organisations', ['q' => 'Ba'], '/api/organizations?q=Ba');
    }

    public function testTheAiReadsAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-organisation', ['id' => $babbage], '/api/organizations/'.$babbage);
    }

    public function testAnUnknownResourceIsRefusedAsByItsEndpoint(): void
    {
        $unknown = self::newIdentity();

        $this->assertTheToolAnswersAsItsEndpoint('lire-organisation', ['id' => $unknown], '/api/organizations/'.$unknown);
    }

    public function testAnInvalidArgumentIsRefusedWithTheViolationsOfItsEndpoint(): void
    {
        $tooLong = str_repeat('a', 101);
        $this->login();
        $violations = $this->requestJson('GET', '/api/organizations?q='.$tooLong)['violations'];

        $refusal = $this->answerOf($this->callWithoutHandshake('rechercher-organisations', ['q' => $tooLong]));

        $this->assertSame($violations, $refusal['violations']);
    }

    public function testTheAiSearchesTheEstablishmentsOfAnOrganizationInTheDirectory(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(OutsideServices::class)->directoryLists(self::BABBAGE_COMPANY);

        $this->assertTheToolAnswersAsItsEndpoint('chercher-etablissements', ['id' => $babbage], '/api/organizations/'.$babbage.'/establishments');
    }

    public function testTheAiFollowsTheTimelineOfAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-contact', ['id' => $ada], '/api/contacts/'.$ada.'/exchanges');
    }

    public function testTheAiReadsTheExchangesToComeWithAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-a-venir-contact', ['id' => $ada], '/api/contacts/'.$ada.'/upcoming-exchanges');
    }

    public function testTheAiReadsWhereToMeetAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('proposer-lieu-de-rendez-vous', ['id' => $ada], '/api/contacts/'.$ada.'/meeting-place');
    }

    public function testTheAiReadsAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-contact', ['id' => $ada], '/api/contacts/'.$ada);
    }

    public function testTheAiOfTheAssistantReadsTheNameInClearAndTheRestMasked(): void
    {
        $ada = $this->ada();
        $this->login('assistant-user');

        $contact = $this->answerOf($this->callWithoutHandshake('lire-contact', ['id' => $ada]));

        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $contact['name']);
        $this->assertNotSame('ada@babbage.test', $contact['contactDetails']['email']);
        $this->assertNotSame('03 88 00 00 42', $contact['contactDetails']['phoneNumber']);
    }

    public function testTheAiSearchesTheContacts(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('rechercher-contacts', ['q' => 'Love'], '/api/contacts?q=Love');
    }

    public function testTheAiSearchesTheContactsByTheirTags(): void
    {
        $this->requestJson('POST', '/api/contacts/'.$this->ada().'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('rechercher-contacts-par-tags', ['tags' => 'CLIENT'], '/api/tagged-contacts?tags=CLIENT');
    }

    public function testTheAiReadsTheOrganizationsOfAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-organisations-du-contact', ['id' => $ada], '/api/contacts/'.$ada.'/organizations');
    }

    public function testTheAiReadsTheOrganizationProposedForAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-organisation-proposee', ['id' => $ada], '/api/contacts/'.$ada.'/proposed-organization');
    }

    public function testTheAiReadsTheContactsOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-contacts-organisation', ['id' => $babbage], '/api/organizations/'.$babbage.'/contacts');
    }

    public function testTheAiFollowsTheTimelineOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-organisation', ['id' => $babbage], '/api/organizations/'.$babbage.'/exchanges');
    }

    public function testTheAiReadsTheExchangesToComeWithAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-a-venir-organisation', ['id' => $babbage], '/api/organizations/'.$babbage.'/upcoming-exchanges');
    }

    public function testTheAiReadsTheExchangesToMakeConformOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-a-mettre-en-conformite', ['id' => $babbage], '/api/organizations/'.$babbage.'/exchanges-to-make-conform');
    }

    public function testTheAiRecognizesWhoCalls(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('reconnaitre-appelant', ['phoneNumber' => '0388000042'], '/api/phone-numbers/0388000042');
    }

    public function testTheAiReadsTheCalendarOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'lire-agenda',
            ['from' => '2026-10-12T00:00:00+02:00', 'to' => '2026-10-19T00:00:00+02:00', 'timeZone' => 'Europe/Paris'],
            '/api/me/calendar?from=2026-10-12T00:00:00%2B02:00&to=2026-10-19T00:00:00%2B02:00&timeZone=Europe/Paris',
        );
    }

    public function testTheAiReadsTheDayOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'lire-ma-journee',
            ['from' => '2026-10-15T00:00:00+02:00', 'to' => '2026-10-16T00:00:00+02:00'],
            '/api/me/day?from=2026-10-15T00:00:00%2B02:00&to=2026-10-16T00:00:00%2B02:00',
        );
    }

    public function testTheAiReadsWhatItsMemberHasToProcess(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('lire-echanges-a-traiter', ['timeZone' => 'Europe/Paris'], '/api/me/exchanges-to-process?timeZone=Europe/Paris');
    }

    public function testTheAiReadsTheExchangesASlotOverlaps(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'lire-echanges-qui-chevauchent',
            ['startsAt' => '2026-10-13T10:00:00+02:00', 'durationInMinutes' => 60],
            '/api/me/planned-exchanges?startsAt=2026-10-13T10:00:00%2B02:00&durationInMinutes=60',
        );
    }

    public function testTheAiReadsAnExchange(): void
    {
        $exchange = $this->anExchangeWithAda();

        $this->assertTheToolAnswersAsItsEndpoint('lire-echange', ['id' => $exchange], '/api/exchanges/'.$exchange);
    }

    public function testTheAiReadsAFileJoinedToAnExchangeByALinkSignedForAMoment(): void
    {
        $exchange = $this->anExchangeWithAda();
        $upload = $this->requestJson('POST', '/api/uploads')['upload'];
        self::getContainer()->get(InMemoryPrivateStorage::class)->send($upload, 2048, 'application/pdf');
        $attachment = $this->requestJson('POST', '/api/exchanges/'.$exchange.'/attachments', ['upload' => $upload, 'name' => 'devis.pdf'])['attachments'][0]['id'];

        $this->assertTheToolAnswersAsItsEndpoint('telecharger-piece-jointe', ['id' => $exchange, 'attachmentId' => $attachment], '/api/exchanges/'.$exchange.'/attachments/'.$attachment);
    }

    public function testTheAiReadsWhereThePushOfAnExchangeIntoTheCalendarStands(): void
    {
        $exchange = $this->anExchangeWithAda();

        $this->assertTheToolAnswersAsItsEndpoint('lire-synchronisation-google', ['id' => $exchange], '/api/exchanges/'.$exchange.'/google-sync');
    }

    public function testTheAiReadsTheConnectionsOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('lire-mes-connexions', [], '/api/me/connections');
    }

    public function testTheAiOfAnAdministratorReadsTheClientOfGoogleOfItsAccount(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('lire-client-google', [], '/api/me/organization/clients/google');
    }

    public function testTheAiReadsWhatItsMemberStillHasToDo(): void
    {
        $this->aToDoAboutAda();

        $this->assertTheToolAnswersAsItsEndpoint('lire-mes-a-faire', [], '/api/me/to-dos');
    }

    public function testTheAiReadsAToDo(): void
    {
        $toDo = $this->aToDoAboutAda();

        $this->assertTheToolAnswersAsItsEndpoint('lire-a-faire', ['id' => $toDo], '/api/to-dos/'.$toDo);
    }

    public function testTheAiReadsTheToDosOfAContact(): void
    {
        $this->aToDoAboutAda();
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('lire-a-faire-contact', ['id' => $ada], '/api/contacts/'.$ada.'/to-dos');
    }

    public function testTheAiReadsTheToDosOfAnOrganization(): void
    {
        $this->aToDoAboutAda();
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('lire-a-faire-organisation', ['id' => $babbage], '/api/organizations/'.$babbage.'/to-dos');
    }

    public function testTheAiReadsTheTagsOfAContact(): void
    {
        $ada = $this->ada();
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('lire-tags-contact', ['id' => $ada], '/api/contacts/'.$ada.'/tags');
    }

    public function testTheAiReadsTheTagsOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('POST', '/api/organizations/'.$babbage.'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('lire-tags-organisation', ['id' => $babbage], '/api/organizations/'.$babbage.'/tags');
    }

    public function testTheAiReadsTheTagsProposedToTheAccount(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('lire-tags-proposes', [], '/api/tags');
    }

    /**
     * A to-do about Ada Lovelace, due tomorrow.
     */
    private function aToDoAboutAda(): string
    {
        return $this->requestJson('POST', '/api/contacts/'.$this->ada().'/to-dos', ['text' => 'Rappeler Ada', 'dueOn' => '2026-10-16'])['id'];
    }

    /**
     * The kernel kept between the requests, for a double to answer each: the tokens the readers sign in with later
     * are checked on the clock of the tokens frozen when it booted, set a minute later so that none is issued after it.
     */
    private function keepTheKernel(): void
    {
        $this->client->disableReboot();
        self::getContainer()->get('app.token_clock')->modify('+1 minute');
    }

    /**
     * The last exchange with Ada Lovelace.
     */
    private function anExchangeWithAda(): string
    {
        return $this->requestJson('GET', '/api/contacts/'.$this->ada().'/exchanges')['items'][0]['id'];
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
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/organizations?q=Babbage')['items'][0]['id'];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function assertTheToolAnswersAsItsEndpoint(string $tool, array $arguments, string $endpoint): void
    {
        foreach (self::READERS as $reader) {
            $this->login($reader);
            $answer = $this->requestJson('GET', $endpoint);

            $this->assertSame($answer, $this->answerOf($this->callWithoutHandshake($tool, $arguments)), sprintf('%s read by %s', $tool, $reader));
        }
    }

    /**
     * What the tool answered: its result, or the problem of its refusal.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function answerOf(array $result): array
    {
        return true === ($result['isError'] ?? false)
            ? json_decode($result['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR)
            : $result['structuredContent'];
    }
}
