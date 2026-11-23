<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\InMemoryStorage;

#[Group('api')]
class OrganizationApiTest extends ApiTestCase
{
    use ReadsResources;

    private const array ACME = [
        'legalIdentity' => ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => null],
        'contactDetails' => ['email' => 'contact@acme.test', 'phoneNumber' => null, 'language' => null, 'links' => [], 'website' => null],
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
        $id = self::stringAt($started, 'id');
        $this->assertResponseHeaderSame('Location', '/api/organizations/'.$id);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);
        $this->assertSame('ACME SAS', $started['name']);
        $this->assertSame([...self::ACME['legalIdentity'], 'siret' => null], $started['legalIdentity']);
        $this->assertSame(self::ACME['contactDetails'], $started['contactDetails']);
        $this->assertSame(self::ACME['address'], $started['address']);
        $this->assertSame($started, $this->requestJson('GET', '/api/organizations/'.$id));
    }

    public function testTheOrganizationKeepsWhenItWasCreatedAndLastChanged(): void
    {
        $id = $this->started(self::ACME);
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
        $this->assertCount(1, self::arrayAt($page, 'items'));
        $this->assertSame('ACME SAS', self::valueAt($page, 'items', 0, 'name'));
        $this->assertSame('Paris', self::valueAt($page, 'items', 0, 'city'));
        $this->assertArrayNotHasKey('next', self::recordAt($page, '_links'));
    }

    public function testTheNextSliceIsReachedByTheLinkNext(): void
    {
        $created = array_map(static fn (int $number): string => sprintf('Prospect %02d', $number), range(1, 21));

        foreach ($created as $name) {
            $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]);
        }

        $slice = $this->requestJson('GET', '/api/organizations?q=prospect');
        $names = array_column(self::recordsAt($slice, 'items'), 'name');
        $this->assertArrayHasKey('next', self::recordAt($slice, '_links'));

        while (array_key_exists('next', self::recordAt($slice, '_links'))) {
            $slice = $this->requestJson('GET', self::stringAt($slice, '_links', 'next', 'href'));
            $names = [...$names, ...array_column(self::recordsAt($slice, 'items'), 'name')];
        }

        $this->assertSame(array_reverse($created), $names);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $id = $this->started(self::ACME);

        $revised = $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME Corp', 'form' => 'SA', 'type' => null]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('ACME Corp', self::valueAt($revised, 'legalIdentity', 'corporateName'));
        $this->assertSame(self::ACME['contactDetails'], $revised['contactDetails']);

        $contacted = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['email' => 'hello@acme.test', 'phoneNumber' => '0388000000', 'language' => 'FR']);
        $this->assertSame('hello@acme.test', self::valueAt($contacted, 'contactDetails', 'email'));
        $this->assertSame('+33 3 88 00 00 00', self::valueAt($contacted, 'contactDetails', 'phoneNumber'));
        $this->assertSame('ACME Corp', self::valueAt($contacted, 'legalIdentity', 'corporateName'));

        $relocated = $this->requestJson('PATCH', '/api/organizations/'.$id.'/address', []);
        $this->assertNull($relocated['address']);

        $noted = $this->requestJson('PATCH', '/api/organizations/'.$id.'/observations', ['observations' => 'Call back']);
        $this->assertSame('Call back', $noted['observations']);
    }

    public function testTheSiretIsNotedWithTheLegalIdentity(): void
    {
        $id = $this->started(self::ACME);

        $revised = $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => null, 'siret' => '552 100 554 00013']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('55210055400013', self::valueAt($revised, 'legalIdentity', 'siret'));
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('everyProspector')]
    public function testEveryProspectorNotesASiretWithOrWithoutTheRight(string $member, array $roles): void
    {
        $id = $this->started(self::ACME);
        $this->login($this->aMemberOfTheFixtures($member, ...$roles));
        $this->assertArrayHasKey('reviseLegalIdentity', $this->linksOf($id));

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME', 'siret' => '55210055400013']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string, list<Role>}>
     */
    public static function everyProspector(): iterable
    {
        yield 'without the right to manage personal data' => ['prospector-without-the-right', [Role::User, Role::Prospector]];
        yield 'an administrator' => ['administrator', [Role::Prospector, Role::Admin]];
    }

    public function testAnInvalidSiretIsRefusedOnItsField(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME', 'siret' => '55210055400014']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['siret'], 'invalidSiret'], [$this->violatedFields($problem), $problem['reason']]);
    }

    public function testASiretOfAnotherOrganizationOfTheAccountIsRefusedNamingIt(): void
    {
        $globex = $this->started(['legalIdentity' => ['corporateName' => 'Globex']]);
        $this->requestJson('PATCH', '/api/organizations/'.$globex.'/legal-identity', ['corporateName' => 'Globex', 'siret' => '55210055400013']);
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/legal-identity', ['corporateName' => 'ACME', 'siret' => '55210055400013']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['siret'], 'siretAlreadyUsed', ['organization' => 'Globex']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    public function testTheLinksAndTheWebsiteAreNotedWithTheContactDetails(): void
    {
        $id = $this->started(self::ACME);

        $changed = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['HTTPS://www.Welcometothejungle.com/fr/companies/ACME/jobs'], 'website' => 'HTTPS://www.Acme.fr/contact']);

        $this->assertResponseIsSuccessful();
        $this->assertSame([[['source' => 'welcomeToTheJungle', 'identifier' => 'acme', 'address' => 'HTTPS://www.Welcometothejungle.com/fr/companies/ACME/jobs']], 'https://www.acme.fr'], [self::valueAt($changed, 'contactDetails', 'links'), self::valueAt($changed, 'contactDetails', 'website')]);
    }

    public function testALinkOfAnotherOrganizationOfTheAccountIsRefusedNamingIt(): void
    {
        $globex = $this->started(['legalIdentity' => ['corporateName' => 'Globex']]);
        $this->requestJson('PATCH', '/api/organizations/'.$globex.'/contact-details', ['links' => ['linkedin.com/company/acme-corp']]);
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['https://www.linkedin.com/company/ACME-Corp/']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'referenceAlreadyUsed', ['holder' => 'Globex']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    public function testMoreThanFiveLinksAreRefusedOnTheirField(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => array_map(static fn (int $site): string => sprintf('https://site%d.test/acme', $site), range(1, 6))]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['links'], $this->violatedFields($problem));
    }

    public function testAWebsiteOfTheDomainOfAnotherOrganizationOfTheAccountIsRefusedNamingIt(): void
    {
        $globex = $this->started(['legalIdentity' => ['corporateName' => 'Globex']]);
        $this->requestJson('PATCH', '/api/organizations/'.$globex.'/contact-details', ['website' => 'https://www.acme.fr']);
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['website' => 'https://acme.fr/blog']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['website'], 'websiteAlreadyUsed', ['organization' => 'Globex']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('everyProspector')]
    public function testEveryProspectorNotesALinkAndAWebsiteWithOrWithoutTheRight(string $member, array $roles): void
    {
        $id = $this->started(self::ACME);
        $this->login($this->aMemberOfTheFixtures($member, ...$roles));
        $this->assertArrayHasKey('changeContactDetails', $this->linksOf($id));

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['linkedin.com/company/acme-corp'], 'website' => 'acme.fr']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('everyProspector')]
    public function testEveryProspectorStartsProspectingWithALinkAndAWebsite(string $member, array $roles): void
    {
        $this->login($this->aMemberOfTheFixtures($member, ...$roles));

        $started = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['links' => ['linkedin.com/company/acme-corp'], 'website' => 'acme.fr']]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([[['source' => 'linkedInCompany', 'identifier' => 'acme-corp', 'address' => 'https://linkedin.com/company/acme-corp']], 'https://acme.fr'], [self::valueAt($started, 'contactDetails', 'links'), self::valueAt($started, 'contactDetails', 'website')]);
    }

    public function testAnOrganizationStartedWithTheLinkOfAnotherIsRefusedNamingIt(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex'], 'contactDetails' => ['links' => ['linkedin.com/company/acme-corp']]]);

        $problem = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['links' => ['linkedin.com/company/acme-corp']]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'referenceAlreadyUsed', ['holder' => 'Globex']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    public function testAPageOfAPersonIsRefusedOnTheLinks(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['https://www.malt.fr/profile/adalovelace']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'referenceOfAnotherKind', ['source' => 'malt', 'designates' => 'person']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    public function testWhatIsNoAddressIsRefusedOnTheLinks(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['ACME sur LinkedIn']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'notAWebAddress'], [$this->violatedFields($problem), $problem['reason']]);
    }

    public function testAPageOfAKnownSourceItDoesNotRecognizeIsRefusedOnTheLinks(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['links' => ['https://www.linkedin.com/school/acme']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'unrecognizedPage', ['host' => 'linkedin.com']], [$this->violatedFields($problem), $problem['reason'], self::valueAt($problem, 'violations', 0, 'context')]);
    }

    public function testAnAddressThatIsNoSiteIsRefusedOnItsField(): void
    {
        $id = $this->started(self::ACME);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['website' => 'acme']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['website'], 'invalidWebsite'], [$this->violatedFields($problem), $problem['reason']]);
    }

    public function testAnOrganizationResemblingAnotherNamesItsPossibleDuplicate(): void
    {
        $acme = $this->started(self::ACME);

        $duplicate = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']]);

        $this->assertSame([[
            'id' => $acme,
            'name' => 'ACME SAS',
            '_links' => [
                'self' => ['href' => '/api/organizations/'.$acme, 'method' => 'GET'],
                'dismiss' => ['href' => '/api/organizations/'.self::stringAt($duplicate, 'id').'/possible-duplicates/'.$acme, 'method' => 'DELETE'],
            ],
        ]], $duplicate['possibleDuplicates']);
    }

    public function testTheListNamesThePossibleDuplicateOfAnOrganization(): void
    {
        $acme = $this->started(self::ACME);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']]);

        $found = $this->requestJson('GET', '/api/organizations?q='.urlencode('ACME s.a.s'));

        $this->assertSame([[
            'id' => $acme,
            'name' => 'ACME SAS',
            '_links' => ['self' => ['href' => '/api/organizations/'.$acme, 'method' => 'GET']],
        ]], self::valueAt($found, 'items', 0, 'possibleDuplicates'));
    }

    public function testAProspectorSaysAPossibleDuplicateIsNone(): void
    {
        $acme = $this->started(self::ACME);
        $duplicate = $this->started(['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']]);

        $dismissed = $this->requestJson('DELETE', '/api/organizations/'.$duplicate.'/possible-duplicates/'.$acme);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $dismissed['possibleDuplicates']);
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('everyProspector')]
    public function testEveryProspectorSaysAPossibleDuplicateIsNoneWithOrWithoutTheRight(string $member, array $roles): void
    {
        $acme = $this->started(self::ACME);
        $duplicate = $this->started(['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']]);
        $this->login($this->aMemberOfTheFixtures($member, ...$roles));
        $this->assertArrayHasKey('dismiss', self::recordAt($this->requestJson('GET', '/api/organizations/'.$duplicate), 'possibleDuplicates', 0, '_links'));

        $this->requestJson('DELETE', '/api/organizations/'.$duplicate.'/possible-duplicates/'.$acme);

        $this->assertResponseIsSuccessful();
    }

    public function testAPossibleDuplicateDismissedMeanwhileIsAConflict(): void
    {
        $acme = $this->started(self::ACME);
        $globex = $this->started(['legalIdentity' => ['corporateName' => 'Globex'], 'address' => ['city' => 'Paris']]);

        $problem = $this->requestJson('DELETE', '/api/organizations/'.$globex.'/possible-duplicates/'.$acme);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('notASuspectedDuplicate', $problem['reason']);
    }

    public function testAttachToAndDetachFromAHolding(): void
    {
        $id = $this->started(self::ACME);
        $holdingId = $this->started(['legalIdentity' => ['corporateName' => 'Group']]);

        $this->assertSame($holdingId, $this->requestJson('PATCH', '/api/organizations/'.$id.'/holding', ['holdingId' => $holdingId])['holdingId']);
        $this->assertNull($this->requestJson('DELETE', '/api/organizations/'.$id.'/holding')['holdingId']);
    }

    public function testChangeAndRemoveTheLogo(): void
    {
        $id = $this->started(self::ACME);

        $withLogo = $this->uploadImage('/api/organizations/'.$id.'/logo');

        $this->assertResponseIsSuccessful();
        $logo = self::stringAt($withLogo, 'logo', 'url');
        $this->assertStringEndsWith('.png', $logo);
        $this->assertTrue($this->storage()->has($logo), 'sent to the storage');

        $this->assertNull($this->requestJson('DELETE', '/api/organizations/'.$id.'/logo')['logo']);
        $this->assertFalse($this->storage()->has($logo), 'removed from the storage');
    }

    public function testTheLinksOfferWhatTheStateOfTheOrganizationAllows(): void
    {
        $started = $this->requestJson('POST', '/api/organizations', self::ACME);
        $id = self::stringAt($started, 'id');
        $links = self::recordAt($started, '_links');

        $this->assertSame(['href' => '/api/organizations/'.$id, 'method' => 'GET'], $links['self']);
        $this->assertSame(['href' => '/api/organizations/'.$id.'/address', 'method' => 'PATCH'], $links['relocate']);
        $this->assertSame(['href' => '/api/organizations/'.$id.'/logo', 'method' => 'POST'], $links['changeLogo']);
        $this->assertArrayNotHasKey('removeLogo', $links, 'no logo to remove');
        $this->assertArrayNotHasKey('detachFromHolding', $links, 'no holding to detach from');

        $withLogo = $this->uploadImage('/api/organizations/'.$id.'/logo');
        $this->assertSame(['href' => '/api/organizations/'.$id.'/logo', 'method' => 'DELETE'], self::valueAt($withLogo, '_links', 'removeLogo'));
    }

    public function testAnIntentionWithoutItsLinkIsAConflict(): void
    {
        $id = $this->started(self::ACME);

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
        $id = $this->started(self::ACME);

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
        $mine = $this->started(self::ACME);
        $intentions = [
            ['GET', '', null], ['PATCH', '/legal-identity', []], ['PATCH', '/contact-details', []], ['PATCH', '/address', []],
            ['PATCH', '/observations', []], ['PATCH', '/holding', ['holdingId' => $mine]], ['DELETE', '/holding', null], ['DELETE', '/logo', null],
            ['POST', '/contacts', ['personId' => self::newIdentity()]], ['DELETE', '/contacts/'.self::newIdentity(), null], ['DELETE', '', null],
            ['GET', '/contacts', null], ['GET', '/exchanges', null], ['GET', '/exchanges-to-redact', null],
            ['POST', '/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]],
            ['GET', '/to-dos', null], ['POST', '/to-dos', ['text' => 'Appeler', 'dueOn' => '2026-10-16']],
            ['DELETE', '/possible-duplicates/'.$mine, null],
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
        $id = $this->started(self::ACME);

        $this->requestJson('PATCH', '/api/organizations/'.$id.'/holding', ['holdingId' => $this->anOrganizationOfAnotherOrganization()]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutTheRightTheContactsAreNotManaged(): void
    {
        $id = $this->started(self::ACME);
        $contact = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$id]]), 'id');
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $links = $this->linksOf($id);
        $this->assertSame([], array_values(array_intersect(['addContact', 'stopProspecting', 'noteExchange', 'toDos'], array_keys($links))));
        $this->assertArrayHasKey('exchanges', $links, 'its timeline is read without the right');

        foreach ([['POST', '/contacts', ['personId' => $contact]], ['DELETE', '/contacts/'.$contact, null], ['DELETE', '', null], ['POST', '/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]]] as [$method, $path, $payload]) {
            $this->requestJson($method, '/api/organizations/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(403, $method.' '.$path);
        }
    }

    public function testAnAdministratorManagesThePersonalDataWithoutTheirRole(): void
    {
        $id = $this->started(self::ACME);
        $contact = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$id]]), 'id');
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $links = $this->linksOf($id);
        $this->assertSame(['addContact', 'stopProspecting', 'noteExchange', 'toDos'], array_values(array_intersect(['addContact', 'stopProspecting', 'noteExchange', 'toDos'], array_keys($links))));

        $this->requestJson('POST', '/api/organizations/'.$id.'/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound'], 'personId' => $contact]);
        $this->assertResponseStatusCodeSame(201);
        $this->requestJson('DELETE', '/api/organizations/'.$id.'/contacts/'.$contact);
        $this->assertResponseIsSuccessful();
        $this->requestJson('DELETE', '/api/organizations/'.$id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testWithoutTheRightAnOrganizationWithoutContactsIsStillLeft(): void
    {
        $id = $this->started(self::ACME);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayHasKey('stopProspecting', $this->linksOf($id));
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
        $accounts = self::service(AccountRepository::class);
        $organizations = self::service(OrganizationRepository::class);
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $accounts->save($competitor);
        $theirProspector = new Prospector(new MemberId(self::newIdentity()), $competitor->id());
        $prospect = Organization::prospectedBy(new Gesture($theirProspector->accountId, $theirProspector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity('Their prospect'), new ContactDetails());
        $organizations->save($prospect);

        return $prospect->id()->value;
    }

    private function aUserWhoIsNotAProspector(): string
    {
        return $this->aMemberOfTheFixtures('assistant', Role::User);
    }

    private function storage(): InMemoryStorage
    {
        return self::service(InMemoryStorage::class);
    }

    /**
     * @param array<string, mixed> $organization
     */
    private function started(array $organization): string
    {
        return self::stringAt($this->requestJson('POST', '/api/organizations', $organization), 'id');
    }

    /**
     * @return array<string, mixed>
     */
    private function linksOf(string $organization): array
    {
        return self::recordAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links');
    }
}
