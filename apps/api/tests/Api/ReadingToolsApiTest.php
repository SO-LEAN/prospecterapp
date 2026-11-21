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
        $this->assertTheToolAnswersAsItsEndpoint('search-organizations', ['q' => 'Ba'], '/api/organizations?q=Ba');
    }

    public function testTheAiReadsAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('get-organization', ['id' => $babbage], '/api/organizations/'.$babbage);
    }

    public function testAnUnknownResourceIsRefusedAsByItsEndpoint(): void
    {
        $unknown = self::newIdentity();

        $this->assertTheToolAnswersAsItsEndpoint('get-organization', ['id' => $unknown], '/api/organizations/'.$unknown);
    }

    public function testAnInvalidArgumentIsRefusedWithTheViolationsOfItsEndpoint(): void
    {
        $tooLong = str_repeat('a', 101);
        $this->login();
        $violations = $this->requestJson('GET', '/api/organizations?q='.$tooLong)['violations'];

        $refusal = $this->answerOf($this->callWithoutHandshake('search-organizations', ['q' => $tooLong]));

        $this->assertSame($violations, $refusal['violations']);
    }

    public function testTheAiSearchesTheEstablishmentsOfAnOrganizationInTheDirectory(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(OutsideServices::class)->directoryLists(self::BABBAGE_COMPANY);

        $this->assertTheToolAnswersAsItsEndpoint('search-company-directory', ['id' => $babbage], '/api/organizations/'.$babbage.'/establishments');
    }

    public function testAServiceOutOfReachIsToTryAgainLaterAsByItsEndpoint(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('search-company-directory', ['id' => $babbage], '/api/organizations/'.$babbage.'/establishments');
    }

    public function testTheAiFollowsTheTimelineOfAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('list-contact-exchanges', ['id' => $ada], '/api/contacts/'.$ada.'/exchanges');
    }

    public function testTheAiReadsTheExchangesToComeWithAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('list-contact-upcoming-exchanges', ['id' => $ada], '/api/contacts/'.$ada.'/upcoming-exchanges');
    }

    public function testTheAiReadsWhereToMeetAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('get-meeting-place', ['id' => $ada], '/api/contacts/'.$ada.'/meeting-place');
    }

    public function testTheAiReadsAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('get-contact', ['id' => $ada], '/api/contacts/'.$ada);
    }

    public function testTheAiOfTheAssistantReadsTheNameInClearAndTheRestMasked(): void
    {
        $ada = $this->ada();
        $this->login('assistant-user');

        $contact = $this->answerOf($this->callWithoutHandshake('get-contact', ['id' => $ada]));

        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $contact['name']);
        $this->assertNotSame('ada@babbage.test', $contact['contactDetails']['email']);
        $this->assertNotSame('03 88 00 00 42', $contact['contactDetails']['phoneNumber']);
    }

    public function testTheAiSearchesTheContacts(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('search-contacts', ['q' => 'Love'], '/api/contacts?q=Love');
    }

    public function testTheAiSearchesTheContactsByTheirTags(): void
    {
        $this->requestJson('POST', '/api/contacts/'.$this->ada().'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('search-contacts-by-tags', ['tags' => 'CLIENT'], '/api/tagged-contacts?tags=CLIENT');
    }

    public function testTheAiReadsTheOrganizationsOfAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('list-contact-organizations', ['id' => $ada], '/api/contacts/'.$ada.'/organizations');
    }

    public function testTheAiReadsTheOrganizationProposedForAContact(): void
    {
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('get-proposed-organization', ['id' => $ada], '/api/contacts/'.$ada.'/proposed-organization');
    }

    public function testTheAiReadsTheContactsOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('list-organization-contacts', ['id' => $babbage], '/api/organizations/'.$babbage.'/contacts');
    }

    public function testTheAiFollowsTheTimelineOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('list-organization-exchanges', ['id' => $babbage], '/api/organizations/'.$babbage.'/exchanges');
    }

    public function testTheAiReadsTheExchangesToComeWithAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('list-organization-upcoming-exchanges', ['id' => $babbage], '/api/organizations/'.$babbage.'/upcoming-exchanges');
    }

    public function testTheAiReadsTheExchangesToMakeConformOfAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('list-exchanges-pending-redaction', ['id' => $babbage], '/api/organizations/'.$babbage.'/exchanges-to-make-conform');
    }

    public function testTheAiRecognizesWhoCalls(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('get-caller', ['phoneNumber' => '0388000042'], '/api/phone-numbers/0388000042');
    }

    public function testTheAiReadsTheCalendarOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'get-calendar',
            ['from' => '2026-10-12T00:00:00+02:00', 'to' => '2026-10-19T00:00:00+02:00', 'timeZone' => 'Europe/Paris'],
            '/api/me/calendar?from=2026-10-12T00:00:00%2B02:00&to=2026-10-19T00:00:00%2B02:00&timeZone=Europe/Paris',
        );
    }

    public function testTheAiReadsTheDayOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'get-my-day',
            ['from' => '2026-10-15T00:00:00+02:00', 'to' => '2026-10-16T00:00:00+02:00'],
            '/api/me/day?from=2026-10-15T00:00:00%2B02:00&to=2026-10-16T00:00:00%2B02:00',
        );
    }

    public function testTheAiReadsWhatItsMemberHasToProcess(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('list-exchanges-to-handle', ['timeZone' => 'Europe/Paris'], '/api/me/exchanges-to-process?timeZone=Europe/Paris');
    }

    public function testTheAiReadsTheExchangesASlotOverlaps(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint(
            'list-overlapping-exchanges',
            ['startsAt' => '2026-10-13T10:00:00+02:00', 'durationInMinutes' => 60],
            '/api/me/planned-exchanges?startsAt=2026-10-13T10:00:00%2B02:00&durationInMinutes=60',
        );
    }

    public function testTheAiReadsAnExchange(): void
    {
        $exchange = $this->anExchangeWithAda();

        $this->assertTheToolAnswersAsItsEndpoint('get-exchange', ['id' => $exchange], '/api/exchanges/'.$exchange);
    }

    public function testTheAiReadsAFileJoinedToAnExchangeByALinkSignedForAMoment(): void
    {
        $exchange = $this->anExchangeWithAda();
        $upload = $this->requestJson('POST', '/api/uploads')['upload'];
        self::getContainer()->get(InMemoryPrivateStorage::class)->send($upload, 2048, 'application/pdf');
        $attachment = $this->requestJson('POST', '/api/exchanges/'.$exchange.'/attachments', ['upload' => $upload, 'name' => 'devis.pdf'])['attachments'][0]['id'];

        $this->assertTheToolAnswersAsItsEndpoint('get-attachment-link', ['id' => $exchange, 'attachmentId' => $attachment], '/api/exchanges/'.$exchange.'/attachments/'.$attachment);
    }

    public function testTheAiReadsWhereThePushOfAnExchangeIntoTheCalendarStands(): void
    {
        $exchange = $this->anExchangeWithAda();

        $this->assertTheToolAnswersAsItsEndpoint('get-exchange-google-sync', ['id' => $exchange], '/api/exchanges/'.$exchange.'/google-sync');
    }

    public function testTheAiReadsTheConnectionsOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('list-my-connections', [], '/api/me/connections');
    }

    public function testTheAiOfAnAdministratorReadsTheClientOfGoogleOfItsAccount(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('get-google-client', [], '/api/me/organization/clients/google');
    }

    public function testTheAiReadsWhatItsMemberStillHasToDo(): void
    {
        $this->aToDoAboutAda();

        $this->assertTheToolAnswersAsItsEndpoint('list-my-todos', [], '/api/me/to-dos');
    }

    public function testTheAiReadsAToDo(): void
    {
        $toDo = $this->aToDoAboutAda();

        $this->assertTheToolAnswersAsItsEndpoint('get-todo', ['id' => $toDo], '/api/to-dos/'.$toDo);
    }

    public function testTheAiReadsTheToDosOfAContact(): void
    {
        $this->aToDoAboutAda();
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('list-contact-todos', ['id' => $ada], '/api/contacts/'.$ada.'/to-dos');
    }

    public function testTheAiReadsTheToDosOfAnOrganization(): void
    {
        $this->aToDoAboutAda();
        $babbage = $this->babbage();

        $this->assertTheToolAnswersAsItsEndpoint('list-organization-todos', ['id' => $babbage], '/api/organizations/'.$babbage.'/to-dos');
    }

    public function testTheAiReadsTheTagsOfAContact(): void
    {
        $ada = $this->ada();
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('list-contact-tags', ['id' => $ada], '/api/contacts/'.$ada.'/tags');
    }

    public function testTheAiReadsTheTagsOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('POST', '/api/organizations/'.$babbage.'/tags', ['tags' => ['#CLIENT']]);

        $this->assertTheToolAnswersAsItsEndpoint('list-organization-tags', ['id' => $babbage], '/api/organizations/'.$babbage.'/tags');
    }

    public function testTheAiReadsTheTagsProposedToTheAccount(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('list-suggested-tags', [], '/api/tags');
    }

    public function testTheAiListsTheCampaignsOfTheTeam(): void
    {
        $this->aCampaignToAda();

        $this->assertTheToolAnswersAsItsEndpoint('list-campaigns', [], '/api/campaigns');
    }

    public function testTheAiReadsACampaign(): void
    {
        $campaign = $this->aCampaignToAda();

        $this->assertTheToolAnswersAsItsEndpoint('get-campaign', ['id' => $campaign], '/api/campaigns/'.$campaign);
    }

    public function testTheAiReadsTheRecipientsOfACampaign(): void
    {
        $campaign = $this->aCampaignToAda();

        $this->assertTheToolAnswersAsItsEndpoint('list-campaign-recipients', ['id' => $campaign], '/api/campaigns/'.$campaign.'/recipients');
    }

    public function testTheAiReadsTheMessageOfACampaignForARecipient(): void
    {
        $campaign = $this->aCampaignToAda();
        $ada = $this->ada();

        $this->assertTheToolAnswersAsItsEndpoint('get-campaign-message', ['id' => $campaign, 'contactId' => $ada], '/api/campaigns/'.$campaign.'/messages/'.$ada);
    }

    public function testTheAiReadsTheDashboard(): void
    {
        $this->keepTheKernel();

        $this->assertTheToolAnswersAsItsEndpoint('get-dashboard', [], '/api/dashboard');
    }

    public function testTheAiReadsTheAccountOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('get-my-account', [], '/api/me');
    }

    public function testTheAiReadsTheRemindersOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('get-my-reminders', [], '/api/me/reminders');
    }

    public function testTheAiReadsTheBookingPageOfItsMember(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('get-my-booking-page', ['timeZone' => 'Europe/Paris'], '/api/me/booking-page?timeZone=Europe/Paris');
    }

    /**
     * A campaign by e-mail to the clients, frozen: Ada Lovelace, tagged a client, its recipient.
     */
    private function aCampaignToAda(): string
    {
        $this->requestJson('POST', '/api/contacts/'.$this->ada().'/tags', ['tags' => ['#CLIENT']]);
        $campaign = $this->requestJson('POST', '/api/campaigns', ['name' => 'Relance des clients', 'channel' => 'email', 'tags' => ['#CLIENT'], 'message' => 'Bonjour {prénom}', 'subject' => '{prénom}, une question'])['id'];
        $this->requestJson('POST', '/api/campaigns/'.$campaign.'/freezing');

        return $campaign;
    }

    /**
     * A to-do about Ada Lovelace, due tomorrow.
     */
    private function aToDoAboutAda(): string
    {
        return $this->requestJson('POST', '/api/contacts/'.$this->ada().'/to-dos', ['text' => 'Rappeler Ada', 'dueOn' => '2026-10-16'])['id'];
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
}
