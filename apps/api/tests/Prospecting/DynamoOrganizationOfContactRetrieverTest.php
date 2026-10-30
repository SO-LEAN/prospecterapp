<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The organizations naming each contact, read from the links the repository writes, without restoring them
 * (ADR 0088).
 */
class DynamoOrganizationOfContactRetrieverTest extends ApiTestCase
{
    public function testEachContactComesWithTheOrganizationsOfTheAccountNamingThem(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $ada = new PersonId(self::newIdentity());
        $grace = new PersonId(self::newIdentity());
        $alone = new PersonId(self::newIdentity());
        $acme = $this->prospect($prospector, 'ACME', $ada, $grace);
        $group = $this->prospect($prospector, 'Groupe ACME', $ada);
        $this->prospect(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'Another account', $ada);

        $organizations = self::getContainer()->get(OrganizationOfContactRetriever::class)->ofContacts([$ada, $grace, $alone], $prospector->accountId);

        $this->assertEqualsCanonicalizing([$acme->id()->value, $group->id()->value], self::idsOf($organizations[$ada->value]));
        $this->assertSame([$acme->id()->value], self::idsOf($organizations[$grace->value]));
        $this->assertSame('ACME', $organizations[$grace->value][0]->fullName);
        $this->assertSame([], $organizations[$alone->value]);
    }

    private function prospect(Prospector $prospector, string $corporateName, PersonId ...$contacts): Organization
    {
        $organization = Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails(), new \DateTimeImmutable());

        foreach ($contacts as $contact) {
            $organization->addContact($contact, $prospector->id, new \DateTimeImmutable());
        }

        self::getContainer()->get(OrganizationRepository::class)->save($organization);

        return $organization;
    }

    /**
     * @param list<OrganizationOfContact> $organizations
     *
     * @return list<string>
     */
    private static function idsOf(array $organizations): array
    {
        return array_map(static fn (OrganizationOfContact $organization): string => $organization->id, $organizations);
    }
}
