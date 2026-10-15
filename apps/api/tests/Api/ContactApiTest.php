<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The persons my organization prospects: never without an organization (ADR 0042).
 */
#[Group('api')]
class ContactApiTest extends ApiTestCase
{
    private const array ADA = [
        'name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
        'jobTitle' => 'Directrice commerciale',
        'contactDetails' => ['email' => 'ada@analytical-engine.test', 'phoneNumber' => '03 88 00 00 42', 'language' => 'FR'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testCollectAContactOfAnOrganization(): void
    {
        $organization = $this->anOrganization('ACME');

        $collected = $this->collect(self::ADA, $organization);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/contacts/'.$collected['id']);
        $this->assertSame('Directrice commerciale', $collected['jobTitle']);
        $this->assertSame(['self', 'rename', 'changeJobTitle', 'changeContactDetails', 'forget'], array_keys($collected['_links']));
        $this->assertSame([$collected['id']], $this->requestJson('GET', '/api/organizations/'.$organization)['contactIds']);
        $this->assertSame(['ACME'], array_column($this->requestJson('GET', '/api/contacts/'.$collected['id'].'/organizations')['items'], 'name'));
    }

    public function testAContactIsNeverAlone(): void
    {
        $problem = $this->requestJson('POST', '/api/contacts', self::ADA);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['organizationIds'], $this->violatedFields($problem));
    }

    public function testAnUnknownOrganizationIsNotFound(): void
    {
        $this->collect(self::ADA, self::newIdentity());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $id = $this->collect(self::ADA, $this->anOrganization('ACME'))['id'];

        $this->assertSame('Directrice générale', $this->requestJson('PATCH', '/api/contacts/'.$id.'/job-title', ['jobTitle' => 'Directrice générale'])['jobTitle']);
        $renamed = $this->requestJson('PATCH', '/api/contacts/'.$id.'/name', ['firstName' => 'Augusta Ada', 'lastName' => 'King']);
        $this->assertSame(['firstName' => 'Augusta Ada', 'lastName' => 'King'], $renamed['name']);
        $this->assertSame('Directrice générale', $renamed['jobTitle']);
    }

    public function testTheListShowsTheOrganizationsOfEachContact(): void
    {
        $organization = $this->anOrganization('Navy');
        $this->collect(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'contactDetails' => ['email' => 'grace@navy.test']], $organization);

        $page = $this->requestJson('GET', '/api/contacts?q=navy');

        $this->assertSame(1, $page['totalItems']);
        $this->assertSame('Grace', $page['items'][0]['name']['firstName']);
        $this->assertSame([['id' => $organization, 'name' => 'Navy', 'organization' => ['href' => '/api/organizations/'.$organization, 'method' => 'GET']]], $page['items'][0]['organizations']);
        $this->assertSame(['self', 'search'], array_keys($page['_links']));
    }

    public function testWithoutTheRightContactsAreMaskedAndOfferNothing(): void
    {
        $organization = $this->anOrganization('ACME');
        $id = $this->collect(self::ADA, $organization)['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $contact = $this->requestJson('GET', '/api/contacts/'.$id);
        $this->assertTrue($contact['masked']);
        $this->assertSame('A***a', $contact['name']['firstName']);
        $this->assertSame(['self'], array_keys($contact['_links']));
        $this->assertSame(['self'], array_keys($this->requestJson('GET', '/api/contacts')['_links']));

        $this->requestJson('GET', '/api/contacts?q=ada');
        $this->assertResponseStatusCodeSame(403);
        $this->collect(self::ADA, $organization);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTwoContactsDoNotShareAnEmail(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->collect(self::ADA, $organization);

        $problem = $this->collect(self::ADA, $organization);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem), 'the field the core names');
        $this->assertCount(1, $this->requestJson('GET', '/api/organizations/'.$organization)['contactIds']);
    }

    public function testAttachAnExistingContactToAnotherOrganization(): void
    {
        $contact = $this->collect(self::ADA, $this->anOrganization('ACME'))['id'];
        $group = $this->anOrganization('Groupe ACME');

        $this->assertSame([$contact], $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact])['contactIds']);
        $contacts = $this->requestJson('GET', '/api/organizations/'.$group.'/contacts')['items'];
        $this->assertSame('Lovelace', $contacts[0]['name']['lastName']);
        $this->assertSame(['href' => '/api/organizations/'.$group.'/contacts/'.$contact, 'method' => 'DELETE'], $contacts[0]['_links']['removeFromOrganization']);

        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testRemovedFromAnotherOrganizationTheContactStays(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collect(self::ADA, $organization)['id'];
        $group = $this->anOrganization('Groupe ACME');
        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]);

        $this->assertSame([], $this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$contact)['contactIds']);
        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseIsSuccessful();
    }

    public function testRemovedFromItsLastOrganizationTheContactIsErased(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collect(self::ADA, $organization)['id'];

        $this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$contact);

        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnErasedContactLeavesItsOrganizations(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collect(self::ADA, $organization)['id'];

        $this->requestJson('DELETE', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization)['contactIds']);
    }

    public function testStoppingToProspectErasesTheContactsLeftAloneAndDetachesTheSubsidiaries(): void
    {
        $group = $this->anOrganization('Groupe ACME');
        $subsidiary = $this->anOrganization('ACME Alsace');
        $this->requestJson('PATCH', '/api/organizations/'.$subsidiary.'/holding', ['holdingId' => $group]);
        $alone = $this->collect(self::ADA, $group)['id'];
        $shared = $this->collect(['name' => ['firstName' => 'Grace'], 'contactDetails' => ['email' => 'grace@navy.test']], $group)['id'];
        $this->requestJson('POST', '/api/organizations/'.$subsidiary.'/contacts', ['personId' => $shared]);

        $this->requestJson('DELETE', '/api/organizations/'.$group);
        $this->assertResponseStatusCodeSame(204);

        $this->requestJson('GET', '/api/organizations/'.$group);
        $this->assertResponseStatusCodeSame(404);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$subsidiary)['holdingId']);
        $this->requestJson('GET', '/api/contacts/'.$alone);
        $this->assertResponseStatusCodeSame(404);
        $this->requestJson('GET', '/api/contacts/'.$shared);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array<string, mixed>
     */
    private function collect(array $contact, string $organization): array
    {
        return $this->requestJson('POST', '/api/contacts', [...$contact, 'organizationIds' => [$organization]]);
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }
}
