<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;
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
        $this->client->disableReboot();
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
