<?php

namespace Tests\App\Prospecting;

use App\Shared\Persistence\DynamoUnitOfWork;
use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
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

        $organization->relocateTo(new Address(city: 'Strasbourg'), new GeoPoint(7.75, 48.58), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->organizations()->save($organization);

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 2, true, true), $this->retriever()->of($organization->id()));
    }

    public function testTheFactsAreReadAsTheUnitOfWorkIsAboutToWriteThem(): void
    {
        $organization = $this->prospected('ACME');
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();

        $this->organizations()->save($organization);
        $facts = $this->retriever()->of($organization->id());
        $unitOfWork->discard();

        $this->assertEquals(new OrganizationFactsResponse($organization->id()->value, 1, true, false), $facts);
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

    private function prospected(string $name): Organization
    {
        return Organization::prospectedBy($this->prospector, new OrganizationId(self::newIdentity()), new LegalIdentity($name), new ContactDetails(), new \DateTimeImmutable(self::NOW));
    }

    private function organizations(): OrganizationRepository
    {
        return self::getContainer()->get(OrganizationRepository::class);
    }

    private function retriever(): OrganizationFactsRetriever
    {
        return self::getContainer()->get(OrganizationFactsRetriever::class);
    }
}
