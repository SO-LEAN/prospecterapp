<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\OrganizationKeys;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Location\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Location\Location;
use Tests\App\Base\ApiTestCase;

/**
 * The facts of the organizations, read from the items `DynamoOrganizationRepository` writes: the revision it
 * increments at each save, what the open unit of work is about to write, the organizations of an account by slice.
 */
class DynamoOrganizationFactsRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';

    private Prospector $prospector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), true);
    }

    public function testEachSaveIncrementsTheRevision(): void
    {
        $organization = $this->prospected('ACME');
        $this->organizations()->save($organization);

        $organization->relocateTo(new Address(city: 'Strasbourg', country: 'FR'), new Location(new GeoPoint(7.75, 48.58), '67482'), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->organizations()->save($organization);

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 2, true, true, [], '67482', 'FR'), $this->retriever()->of($organization->id()));
    }

    public function testAnOrganizationLocatedBeforeItsMunicipalityWasKeptHasNone(): void
    {
        $organization = $this->prospected('ACME');
        $organization->relocateTo(new Address(city: 'Strasbourg'), new Location(new GeoPoint(7.75, 48.58), '67482'), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->organizations()->save($organization);
        $this->forgetTheMunicipalityOf($organization);

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 1, true, true, [], null, null), $this->retriever()->of($organization->id()));
        $this->assertEquals(new Address(city: 'Strasbourg'), $this->organizations()->get($organization->id())->addressToLocateAgain());
    }

    public function testTheFactsNameTheContactsOfTheOrganization(): void
    {
        $organization = $this->prospected('ACME');
        $ada = new ContactId(self::newIdentity());
        $organization->addContact($ada, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->organizations()->save($organization);

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 1, true, false, [$ada->value], null, null), $this->retriever()->of($organization->id()));
    }

    public function testTheFactsAreReadAsTheUnitOfWorkIsAboutToWriteThem(): void
    {
        $organization = $this->prospected('ACME');
        $unitOfWork = self::service(DynamoUnitOfWork::class);
        $unitOfWork->begin();

        $this->organizations()->save($organization);
        $facts = $this->retriever()->of($organization->id());
        $unitOfWork->discard();

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 1, true, false, [], null, null), $facts);
    }

    public function testARemovedOrganizationIsAbsent(): void
    {
        $organization = $this->prospected('ACME');
        $this->organizations()->save($organization);

        $this->organizations()->remove($organization);

        $this->assertEquals(OrganizationFactsResponse::absent($organization->id()->value), $this->retriever()->of($organization->id()));
    }

    public function testTheFactsOfAnAccountComeBySliceNewestFirst(): void
    {
        $organizations = array_map($this->prospected(...), ['ACME', 'Globex', 'Initech']);
        array_walk($organizations, $this->organizations()->save(...));

        $first = $this->retriever()->sliceOf($this->prospector->accountId, null, 2);
        $next = $this->retriever()->sliceOf($this->prospector->accountId, $first->next, 2);

        $this->assertSame([$organizations[2]->id()->value, $organizations[1]->id()->value], array_map(static fn (OrganizationFactsResponse $facts): string => $facts->organizationId, $first->content));
        $this->assertSame([$organizations[0]->id()->value], array_map(static fn (OrganizationFactsResponse $facts): string => $facts->organizationId, $next->content));
        $this->assertNull($next->next);
    }

    public function testAnUnknownAccountHasNoFacts(): void
    {
        $this->assertSame([], $this->retriever()->sliceOf(new AccountId(self::newIdentity()), null, 10)->content);
    }

    /**
     * Its item as written before the organizations kept their municipality.
     */
    private function forgetTheMunicipalityOf(Organization $organization): void
    {
        $table = self::tableOf('prospecting');
        $item = self::storedItem($table, OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $document = Typed::document($item['organization']);
        unset($document['municipalityCode']);
        $table->batch([['organization' => $document] + $item]);
    }

    private function prospected(string $name): Organization
    {
        return Organization::prospectedBy(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new OrganizationId(self::newIdentity()), new LegalIdentity($name), new ContactDetails());
    }

    private function organizations(): OrganizationRepository
    {
        return self::service(OrganizationRepository::class);
    }

    private function retriever(): OrganizationFactsRetriever
    {
        return self::service(OrganizationFactsRetriever::class);
    }
}
