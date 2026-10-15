<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The persons my organization prospects, and the contacts of a prospected organization (ADR 0041).
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

    public function testCollectThenConsultAContact(): void
    {
        $collected = $this->requestJson('POST', '/api/contacts', self::ADA);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/contacts/'.$collected['id']);
        $this->assertSame(self::ADA['name'], $collected['name']);
        $this->assertSame('Directrice commerciale', $collected['jobTitle']);
        $this->assertFalse($collected['masked']);
        $this->assertSame(['self', 'rename', 'changeJobTitle', 'changeContactDetails', 'forget'], array_keys($collected['_links']));
        $this->assertSame($collected, $this->requestJson('GET', '/api/contacts/'.$collected['id']));
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $id = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];

        $this->assertSame('Directrice générale', $this->requestJson('PATCH', '/api/contacts/'.$id.'/job-title', ['jobTitle' => 'Directrice générale'])['jobTitle']);
        $renamed = $this->requestJson('PATCH', '/api/contacts/'.$id.'/name', ['firstName' => 'Augusta Ada', 'lastName' => 'King']);
        $this->assertSame(['firstName' => 'Augusta Ada', 'lastName' => 'King'], $renamed['name']);
        $this->assertSame('Directrice générale', $renamed['jobTitle']);
        $changed = $this->requestJson('PATCH', '/api/contacts/'.$id.'/contact-details', ['email' => 'ada@king.test', 'phoneNumber' => null, 'language' => null]);
        $this->assertSame('ada@king.test', $changed['contactDetails']['email']);
    }

    public function testSearchByNameOrEmail(): void
    {
        $this->requestJson('POST', '/api/contacts', self::ADA);
        $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'contactDetails' => ['email' => 'grace@navy.test']]);

        $page = $this->requestJson('GET', '/api/contacts?q=navy');

        $this->assertSame(1, $page['totalItems']);
        $this->assertSame('Grace', $page['items'][0]['name']['firstName']);
        $this->assertSame(['self', 'collect', 'search'], array_keys($page['_links']));
    }

    public function testWithoutTheRightContactsAreMaskedAndOfferNothing(): void
    {
        $id = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $contact = $this->requestJson('GET', '/api/contacts/'.$id);
        $this->assertTrue($contact['masked']);
        $this->assertSame('A***a', $contact['name']['firstName']);
        $this->assertSame(['self'], array_keys($contact['_links']));

        $this->assertSame(['self'], array_keys($this->requestJson('GET', '/api/contacts')['_links']));
        $this->requestJson('GET', '/api/contacts?q=ada');
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('POST', '/api/contacts', self::ADA);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('DELETE', '/api/contacts/'.$id);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTwoContactsDoNotShareAnEmail(): void
    {
        $this->requestJson('POST', '/api/contacts', self::ADA);

        $problem = $this->requestJson('POST', '/api/contacts', self::ADA);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem), 'the field the core names');
    }

    public function testAttachAContactThenReadTheContactsOfTheOrganization(): void
    {
        $contact = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];
        $organization = $this->anOrganization();

        $attached = $this->requestJson('POST', '/api/organizations/'.$organization.'/contacts', ['personId' => $contact]);
        $this->assertResponseIsSuccessful();
        $this->assertSame([$contact], $attached['contactIds']);

        $contacts = $this->requestJson('GET', '/api/organizations/'.$organization.'/contacts')['items'];
        $this->assertCount(1, $contacts);
        $this->assertSame('Lovelace', $contacts[0]['name']['lastName']);
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/contacts/'.$contact, 'method' => 'DELETE'], $contacts[0]['_links']['removeFromOrganization']);
    }

    public function testAContactIsAttachedOnce(): void
    {
        $contact = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];
        $organization = $this->anOrganization();
        $this->requestJson('POST', '/api/organizations/'.$organization.'/contacts', ['personId' => $contact]);

        $problem = $this->requestJson('POST', '/api/organizations/'.$organization.'/contacts', ['personId' => $contact]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame(['personId'], $this->violatedFields($problem));
    }

    public function testAnUnknownPersonIsNotAttached(): void
    {
        $this->requestJson('POST', '/api/organizations/'.$this->anOrganization().'/contacts', ['personId' => self::newIdentity()]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRemoveAContactKeepsThePerson(): void
    {
        $contact = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];
        $organization = $this->anOrganization();
        $this->requestJson('POST', '/api/organizations/'.$organization.'/contacts', ['personId' => $contact]);

        $this->assertSame([], $this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$contact)['contactIds']);
        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseIsSuccessful();
    }

    public function testAnErasedContactIsLeftOutOfTheOrganization(): void
    {
        $contact = $this->requestJson('POST', '/api/contacts', self::ADA)['id'];
        $organization = $this->anOrganization();
        $this->requestJson('POST', '/api/organizations/'.$organization.'/contacts', ['personId' => $contact]);

        $this->requestJson('DELETE', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(204);

        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/contacts')['items']);
    }

    private function anOrganization(): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
    }
}
