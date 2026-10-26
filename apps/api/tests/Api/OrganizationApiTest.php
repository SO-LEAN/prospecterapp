<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemoryStorage;

#[Group('api')]
class OrganizationApiTest extends ApiTestCase
{
    private const array ACME = [
        'legalIdentity' => ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => null],
        'contactDetails' => ['email' => 'contact@acme.test', 'phoneNumber' => null, 'language' => null],
        'address' => ['street' => '1 rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'country' => 'FR'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testStartProspectingThenConsult(): void
    {
        $started = $this->requestJson('POST', '/api/organizations', self::ACME);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/organizations/'.$started['id']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $started['id']);
        $this->assertSame('ACME SAS', $started['name']);
        $this->assertSame(self::ACME['legalIdentity'], $started['legalIdentity']);
        $this->assertSame(self::ACME['contactDetails'], $started['contactDetails']);
        $this->assertSame(self::ACME['address'], $started['address']);
        $this->assertSame($started, $this->requestJson('GET', '/api/organizations/'.$started['id']));
    }

    public function testTheOrganizationKeepsWhenItWasCreatedAndLastChanged(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $relocated = $this->requestJson('PATCH', '/api/organizations/'.$id.'/address', ['city' => 'Colmar']);

        $this->assertSame('2026-10-15T09:00:00+00:00', $relocated['createdAt']);
        $this->assertSame('2026-10-15T09:00:00+00:00', $relocated['modifiedAt'], 'the moment of the command, given by the clock of the API');
    }

    public function testSearchIsPaginatedAndMatchesNames(): void
    {
        $this->requestJson('POST', '/api/organizations', self::ACME);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]);

        $page = $this->requestJson('GET', '/api/organizations?q=acm');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $page['items']);
        $this->assertSame('ACME SAS', $page['items'][0]['name']);
        $this->assertSame('Paris', $page['items'][0]['city']);
        $this->assertArrayNotHasKey('next', $page['_links']);
    }

    public function testTheNextSliceIsReachedByTheLinkNext(): void
    {
        $created = array_map(static fn (int $number): string => sprintf('Prospect %02d', $number), range(1, 21));

        foreach ($created as $name) {
            $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]);
        }

        $slice = $this->requestJson('GET', '/api/organizations?q=prospect');
        $names = array_column($slice['items'], 'name');
        $this->assertArrayHasKey('next', $slice['_links']);

        while (isset($slice['_links']['next'])) {
            $slice = $this->requestJson('GET', $slice['_links']['next']['href']);
            $names = [...$names, ...array_column($slice['items'], 'name')];
        }

        $this->assertSame(array_reverse($created), $names);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];

        $revised = $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME Corp', 'form' => 'SA', 'type' => null]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('ACME Corp', $revised['legalIdentity']['corporateName']);
        $this->assertSame(self::ACME['contactDetails'], $revised['contactDetails']);

        $contacted = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['email' => 'hello@acme.test', 'phoneNumber' => '0388000000', 'language' => 'FR']);
        $this->assertSame('hello@acme.test', $contacted['contactDetails']['email']);
        $this->assertSame('+33 3 88 00 00 00', $contacted['contactDetails']['phoneNumber']);
        $this->assertSame('ACME Corp', $contacted['legalIdentity']['corporateName']);

        $relocated = $this->requestJson('PATCH', '/api/organizations/'.$id.'/address', []);
        $this->assertNull($relocated['address']);

        $noted = $this->requestJson('PATCH', '/api/organizations/'.$id.'/observations', ['observations' => 'Call back']);
        $this->assertSame('Call back', $noted['observations']);
    }

    public function testAttachToAndDetachFromAHolding(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $holdingId = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Group']])['id'];

        $this->assertSame($holdingId, $this->requestJson('PATCH', '/api/organizations/'.$id.'/holding', ['holdingId' => $holdingId])['holdingId']);
        $this->assertNull($this->requestJson('DELETE', '/api/organizations/'.$id.'/holding')['holdingId']);
    }

    public function testChangeAndRemoveTheLogo(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];

        $withLogo = $this->uploadImage('/api/organizations/'.$id.'/logo');

        $this->assertResponseIsSuccessful();
        $this->assertStringEndsWith('.png', $withLogo['logo']['url']);
        $this->assertTrue($this->storage()->has($withLogo['logo']['url']), 'sent to the storage');

        $this->assertNull($this->requestJson('DELETE', '/api/organizations/'.$id.'/logo')['logo']);
        $this->assertFalse($this->storage()->has($withLogo['logo']['url']), 'removed from the storage');
    }

    public function testTheLinksOfferWhatTheStateOfTheOrganizationAllows(): void
    {
        $started = $this->requestJson('POST', '/api/organizations', self::ACME);
        $id = $started['id'];

        $this->assertSame(['href' => '/api/organizations/'.$id, 'method' => 'GET'], $started['_links']['self']);
        $this->assertSame(['href' => '/api/organizations/'.$id.'/address', 'method' => 'PATCH'], $started['_links']['relocate']);
        $this->assertSame(['href' => '/api/organizations/'.$id.'/logo', 'method' => 'POST'], $started['_links']['changeLogo']);
        $this->assertArrayNotHasKey('removeLogo', $started['_links'], 'no logo to remove');
        $this->assertArrayNotHasKey('detachFromHolding', $started['_links'], 'no holding to detach from');

        $withLogo = $this->uploadImage('/api/organizations/'.$id.'/logo');
        $this->assertSame(['href' => '/api/organizations/'.$id.'/logo', 'method' => 'DELETE'], $withLogo['_links']['removeLogo']);
    }

    public function testAnIntentionWithoutItsLinkIsAConflict(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];

        foreach ([['DELETE', '/logo', 'logo'], ['DELETE', '/holding', 'holdingId']] as [$method, $path, $field]) {
            $problem = $this->requestJson($method, '/api/organizations/'.$id.$path);
            $this->assertResponseStatusCodeSame(409, $method.' '.$path);
            $this->assertSame([$field], $this->violatedFields($problem));
        }
    }

    public function testOnlyAProspectorProspects(): void
    {
        $this->login($this->aUserWhoIsNotAProspector());

        $problem = $this->requestJson('POST', '/api/organizations', self::ACME);

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertSame(403, $problem['status']);
    }

    public function testInvalidInputIsRejectedWithViolations(): void
    {
        $problem = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'not an email'], 'address' => ['country' => 'XX']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['contactDetails.email', 'address.country'], $this->violatedFields($problem));
    }

    public function testUnknownFieldsAreRejectedInsteadOfIgnored(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/address', ['street' => '2 rue de la Paix', 'town' => 'Paris']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['town'], $this->violatedFields($problem));
    }

    public function testABusinessRuleRefusalIsAViolation(): void
    {
        $problem = $this->requestJson('POST', '/api/organizations', ['address' => ['city' => 'Paris']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertSame([''], $this->violatedFields($problem));
    }

    public function testAnOrganizationOfAnotherOrganizationIsNotFoundForEveryIntention(): void
    {
        $id = $this->anOrganizationOfAnotherOrganization();
        $mine = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $intentions = [
            ['GET', '', null], ['PATCH', '/legal-identity', []], ['PATCH', '/contact-details', []], ['PATCH', '/address', []],
            ['PATCH', '/observations', []], ['PATCH', '/holding', ['holdingId' => $mine]], ['DELETE', '/holding', null], ['DELETE', '/logo', null],
            ['POST', '/contacts', ['personId' => self::newIdentity()]], ['DELETE', '/contacts/'.self::newIdentity(), null], ['DELETE', '', null],
            ['GET', '/contacts', null], ['GET', '/exchanges', null], ['GET', '/exchanges-to-make-conform', null],
            ['POST', '/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]],
        ];

        foreach ($intentions as [$method, $path, $payload]) {
            $problem = $this->requestJson($method, '/api/organizations/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(404, $method.' '.$path);
            $this->assertSame('unknownOrganization', $problem['reason'], $method.' '.$path);
        }

        $this->assertSame('unknownOrganization', $this->uploadImage('/api/organizations/'.$id.'/logo')['reason']);
        $this->assertResponseStatusCodeSame(404, 'POST /logo');
    }

    public function testAnOrganizationOfAnotherOrganizationIsNotFoundWhateverThePayload(): void
    {
        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->anOrganizationOfAnotherOrganization().'/observations', ['notes' => 'unknown field']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownOrganization', $problem['reason']);
    }

    public function testAHoldingOfAnotherOrganizationIsNotFound(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/holding', ['holdingId' => $this->anOrganizationOfAnotherOrganization()]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutTheRightTheContactsAreNotManaged(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$id]])['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $links = $this->requestJson('GET', '/api/organizations/'.$id)['_links'];
        $this->assertSame([], array_values(array_intersect(['addContact', 'stopProspecting', 'noteExchange'], array_keys($links))));
        $this->assertArrayHasKey('exchanges', $links, 'its timeline is read without the right');

        foreach ([['POST', '/contacts', ['personId' => $contact]], ['DELETE', '/contacts/'.$contact, null], ['DELETE', '', null], ['POST', '/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]]] as [$method, $path, $payload]) {
            $this->requestJson($method, '/api/organizations/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(403, $method.' '.$path);
        }
    }

    public function testAnAdministratorManagesThePersonalDataWithoutTheirRole(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$id]])['id'];
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $links = $this->requestJson('GET', '/api/organizations/'.$id)['_links'];
        $this->assertSame(['addContact', 'stopProspecting', 'noteExchange'], array_values(array_intersect(['addContact', 'stopProspecting', 'noteExchange'], array_keys($links))));

        $this->requestJson('POST', '/api/organizations/'.$id.'/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound'], 'personId' => $contact]);
        $this->assertResponseStatusCodeSame(201);
        $this->requestJson('DELETE', '/api/organizations/'.$id.'/contacts/'.$contact);
        $this->assertResponseIsSuccessful();
        $this->requestJson('DELETE', '/api/organizations/'.$id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testWithoutTheRightAnOrganizationWithoutContactsIsStillLeft(): void
    {
        $id = $this->requestJson('POST', '/api/organizations', self::ACME)['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayHasKey('stopProspecting', $this->requestJson('GET', '/api/organizations/'.$id)['_links']);
        $this->requestJson('DELETE', '/api/organizations/'.$id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testMyOwnOrganizationIsNotAProspectedOne(): void
    {
        $myAccount = $this->accountOfTheFixtures();

        $this->requestJson('GET', '/api/organizations/'.$myAccount->value);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnIdThatIsNotAUuidIsNotFound(): void
    {
        $this->requestJson('GET', '/api/organizations/42');

        $this->assertResponseStatusCodeSame(404);
    }

    private function anOrganizationOfAnotherOrganization(): string
    {
        $accounts = self::getContainer()->get(AccountRepository::class);
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable());
        $accounts->save($competitor);
        $theirProspector = new Prospector(new MemberId(self::newIdentity()), $competitor->id());
        $prospect = Organization::prospectedBy($theirProspector, new OrganizationId(self::newIdentity()), new LegalIdentity('Their prospect'), new ContactDetails(), new \DateTimeImmutable());
        $organizations->save($prospect);

        return $prospect->id()->value;
    }

    private function aUserWhoIsNotAProspector(): string
    {
        return $this->aMemberOfTheFixtures('assistant', Role::User);
    }

    private function storage(): InMemoryStorage
    {
        return self::getContainer()->get(InMemoryStorage::class);
    }
}
