<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * The retriever builds the organizations of a list from the items the repository wrote, without restoring them
 * (ADR 0088).
 */
class DynamoFoundOrganizationRetrieverTest extends ApiTestCase
{
    public function testTheOrganizationsOfAListResemblingOthersNameTheirPossibleDuplicates(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->prospect($prospector, 'Acme SAS');
        $duplicate = $this->prospect($prospector, 'ACME s.a.s');
        $acme->suspectDuplicateOf($duplicate->id(), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $duplicate->suspectDuplicateOf($acme->id(), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        self::getContainer()->get(OrganizationRepository::class)->save($duplicate);

        $found = self::getContainer()->get(FoundOrganizationRetriever::class)->sliceProspectedBy($prospector->accountId, null, null, 10)->organizations;

        $this->assertEquals([[new PossibleDuplicate($acme->id()->value, 'Acme SAS')], [new PossibleDuplicate($duplicate->id()->value, 'ACME s.a.s')]], array_column($found, 'possibleDuplicates'));
    }

    private function prospect(Prospector $prospector, string $corporateName): Organization
    {
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails());
        self::getContainer()->get(OrganizationRepository::class)->save($organization);

        return $organization;
    }
}
