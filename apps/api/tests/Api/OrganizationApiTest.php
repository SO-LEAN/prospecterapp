<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Tests\App\Base\ApiTestCase;

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

    public function testSearchIsPaginatedAndMatchesNames(): void
    {
        $this->requestJson('POST', '/api/organizations', self::ACME);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]);

        $page = $this->requestJson('GET', '/api/organizations?q=acm');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $page['totalItems']);
        $this->assertSame('ACME SAS', $page['items'][0]['name']);
        $this->assertSame('Paris', $page['items'][0]['city']);
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
        $storedFile = $this->storagePath($withLogo['logo']['url']);
        $this->assertFileExists($storedFile);

        $this->assertNull($this->requestJson('DELETE', '/api/organizations/'.$id.'/logo')['logo']);
        $this->assertFileDoesNotExist($storedFile);
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

        foreach ([['GET', ''], ['PATCH', '/legal-identity'], ['PATCH', '/contact-details'], ['PATCH', '/address'], ['PATCH', '/observations'], ['DELETE', '/holding'], ['DELETE', '/logo']] as [$method, $path]) {
            $this->requestJson($method, '/api/organizations/'.$id.$path, 'PATCH' === $method ? [] : null);
            $this->assertResponseStatusCodeSame(404, $method.' '.$path);
        }
    }

    public function testMyOwnOrganizationIsNotAProspectedOne(): void
    {
        $myAccount = $this->fixtures->getReference('solean-it-account', Account::class)->id();

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
        $competitor = Account::open($accounts->nextIdentity(), new LegalIdentity('Competitor'));
        $accounts->save($competitor);
        $theirProspector = new Prospector(self::getContainer()->get(MemberRepository::class)->nextIdentity(), $competitor->id());
        $prospect = Organization::prospectedBy($theirProspector, $organizations->nextIdentity(), new LegalIdentity('Their prospect'), new ContactDetails());
        $organizations->save($prospect);

        return $prospect->id()->value;
    }

    private function aUserWhoIsNotAProspector(): string
    {
        $members = self::getContainer()->get(MemberRepository::class);
        $assistant = Member::register($members->nextIdentity(), $this->fixtures->getReference('solean-it-account', Account::class)->id(), 'assistant', Role::User);
        $members->save($assistant);
        $this->fixtures->setReference('assistant', $assistant);

        return 'assistant';
    }

    private function storagePath(string $url): string
    {
        return str_replace($_ENV['APP_STORAGE_URL'], self::getContainer()->getParameter('kernel.project_dir').'/public/static/storage/test', $url);
    }
}
