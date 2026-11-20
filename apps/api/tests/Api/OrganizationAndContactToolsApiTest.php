<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI manages the organizations and the contacts as its member does on the screens (ADR 0163): each tool of a write
 * does what its endpoint does, the resource it answers that the screen reads then, or refuses as its endpoint refuses,
 * to a prospector, an assistant without the right to manage the personal data, an administrator, a member who is no
 * prospector and a member of another account alike.
 */
#[Group('api')]
class OrganizationAndContactToolsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array WRITERS = ['prospector-user', 'assistant-user', 'admin-user', 'member-user', 'stranger'];

    private const array PROSPECTORS = ['prospector-user', 'assistant-user', 'admin-user'];

    private const array PERSONAL_DATA_MANAGERS = ['prospector-user', 'admin-user'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiChangesTheJobTitleOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-fonction-contact', ['id' => $ada, 'jobTitle' => 'CTO'], 'PATCH', '/api/contacts/'.$ada.'/job-title', ['jobTitle' => 'CTO'], self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('CTO', $changed['jobTitle']);
    }

    public function testTheAiRenamesAContact(): void
    {
        $ada = $this->ada();
        $name = ['firstName' => 'Augusta Ada', 'lastName' => 'King'];

        $renamed = $this->assertTheToolActsAsItsEndpoint('renommer-contact', ['id' => $ada, ...$name], 'PATCH', '/api/contacts/'.$ada.'/name', $name, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame($name, $renamed['name']);
    }

    public function testTheAiChangesTheCivilityOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-civilite-contact', ['id' => $ada, 'civility' => 'ms'], 'PATCH', '/api/contacts/'.$ada.'/civility', ['civility' => 'ms'], self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('ms', $changed['civility']);
    }

    public function testTheAiChangesTheContactDetailsOfAContactAndTheirProfileOnLinkedIn(): void
    {
        $ada = $this->ada();
        $details = ['email' => 'ada@lovelace.test', 'phoneNumber' => '03 88 00 00 43', 'language' => 'GB', 'links' => ['linkedin.com/in/ada-lovelace']];

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-coordonnees-contact', ['id' => $ada, ...$details], 'PATCH', '/api/contacts/'.$ada.'/contact-details', $details, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('ada@lovelace.test', $changed['contactDetails']['email']);
        $this->assertSame(['linkedin', 'ada-lovelace'], [$changed['contactDetails']['links'][0]['source'], $changed['contactDetails']['links'][0]['identifier']]);
    }

    public function testTheAiCollectsAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = ['firstName' => 'Charles', 'lastName' => 'Babbage', 'civility' => 'mr', 'jobTitle' => 'Founder', 'phoneNumber' => '03 88 00 00 44', 'links' => ['linkedin.com/in/charles-babbage'], 'organizationIds' => [$babbage]];
        $payload = ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'civility' => 'mr', 'jobTitle' => 'Founder', 'contactDetails' => ['phoneNumber' => '03 88 00 00 44', 'links' => ['linkedin.com/in/charles-babbage']], 'organizationIds' => [$babbage]];

        $collected = $this->assertTheToolActsAsItsEndpoint('collecter-contact', $charles, 'POST', '/api/contacts', $payload, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame([['firstName' => 'Charles', 'lastName' => 'Babbage'], 'Founder'], [$collected['name'], $collected['jobTitle']]);
    }

    public function testTheAiRelocatesAnOrganization(): void
    {
        $babbage = $this->babbage();
        $address = ['street' => '1 place Kléber', 'postalCode' => '67000', 'city' => 'Strasbourg', 'country' => 'FR'];

        $relocated = $this->assertTheToolActsAsItsEndpoint('demenager-organisation', ['id' => $babbage, ...$address], 'PATCH', '/api/organizations/'.$babbage.'/address', $address, self::PROSPECTORS);

        $this->assertSame($address, $relocated['address']);
    }

    public function testTheAiWritesTheObservationsOnAnOrganization(): void
    {
        $babbage = $this->babbage();

        $written = $this->assertTheToolActsAsItsEndpoint('ecrire-observations-organisation', ['id' => $babbage, 'observations' => 'Rappeler en janvier'], 'PATCH', '/api/organizations/'.$babbage.'/observations', ['observations' => 'Rappeler en janvier'], self::PROSPECTORS);

        $this->assertSame('Rappeler en janvier', $written['observations']);
    }

    public function testTheAiRevisesTheLegalIdentityOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $identity = ['corporateName' => 'Babbage & Sons', 'form' => 'SAS', 'type' => 'Client', 'siret' => '55210055400021'];

        $revised = $this->assertTheToolActsAsItsEndpoint('reviser-identite-organisation', ['id' => $babbage, ...$identity], 'PATCH', '/api/organizations/'.$babbage.'/legal-identity', $identity, self::PROSPECTORS);

        $this->assertSame($identity, $revised['legalIdentity']);
    }

    public function testTheAiChangesTheContactDetailsOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $details = ['email' => 'hello@babbage.test', 'phoneNumber' => '03 88 00 00 45', 'language' => 'FR', 'links' => ['https://www.welcometothejungle.com/fr/companies/babbage'], 'website' => 'www.babbage.test'];

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-coordonnees-organisation', ['id' => $babbage, ...$details], 'PATCH', '/api/organizations/'.$babbage.'/contact-details', $details, self::PROSPECTORS);

        $this->assertSame(['hello@babbage.test', 'https://www.babbage.test'], [$changed['contactDetails']['email'], $changed['contactDetails']['website']]);
    }

    public function testTheAiAttachesAnOrganizationToItsHolding(): void
    {
        $babbage = $this->babbage();
        $holding = $this->aHolding();

        $attached = $this->assertTheToolActsAsItsEndpoint('rattacher-a-holding', ['id' => $babbage, 'holdingId' => $holding], 'PATCH', '/api/organizations/'.$babbage.'/holding', ['holdingId' => $holding], self::PROSPECTORS);

        $this->assertSame($holding, $attached['holdingId']);
    }

    public function testTheAiDetachesAnOrganizationFromItsHoldingOnceItsMemberConfirmed(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('PATCH', '/api/organizations/'.$babbage.'/holding', ['holdingId' => $this->aHolding()]);

        $detached = $this->assertTheToolActsAsItsEndpoint('detacher-de-holding', ['id' => $babbage], 'DELETE', '/api/organizations/'.$babbage.'/holding', null, self::PROSPECTORS);

        $this->assertNull($detached['holdingId']);
        $this->assertTheToolAsksForAConfirmation('detacher-de-holding');
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
     * Analytical Group, an organization the account prospects, to hold others.
     */
    private function aHolding(): string
    {
        $this->login();

        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Analytical Group']])['id'];
    }

    /**
     * The tool says it removes or erases: its AI asks its member before calling it.
     */
    private function assertTheToolAsksForAConfirmation(string $tool): void
    {
        $this->login();
        $listed = array_find($this->callWithHandshake('tools/list', [])['tools'], static fn (array $listed): bool => $tool === $listed['name']);

        $this->assertSame(['readOnlyHint' => false, 'destructiveHint' => true], array_intersect_key($listed['annotations'] ?? [], ['readOnlyHint' => true, 'destructiveHint' => true]));
    }

    /**
     * Each writer calls the tool: refused, the endpoint refuses them the same; done, by a writer the endpoint allows,
     * it answers the resource the screen then reads, by its link `self`.
     *
     * @param array<string, mixed>      $arguments
     * @param array<string, mixed>|null $payload
     * @param list<string>              $allowed   the writers the endpoint allows
     *
     * @return array<string, mixed> what the tool answered the first writer it allowed
     */
    private function assertTheToolActsAsItsEndpoint(string $tool, array $arguments, string $method, string $endpoint, ?array $payload, array $allowed): array
    {
        $done = [];
        foreach (self::WRITERS as $writer) {
            $this->login($writer);
            $result = $this->callWithoutHandshake($tool, $arguments);
            $answer = $this->answerOf($result);

            if (true === ($result['isError'] ?? false)) {
                $this->assertSame($this->requestJson($method, $endpoint, $payload), $answer, sprintf('%s refused to %s', $tool, $writer));
                continue;
            }

            $this->assertContains($writer, $allowed, sprintf('%s done by %s, whom its endpoint refuses', $tool, $writer));
            $this->assertSame($this->requestJson('GET', $answer['_links']['self']['href']), $answer, sprintf('%s done by %s', $tool, $writer));
            $done[] = $answer;
        }

        $this->assertNotSame([], $done, sprintf('%s done by nobody', $tool));

        return $done[0];
    }
}
