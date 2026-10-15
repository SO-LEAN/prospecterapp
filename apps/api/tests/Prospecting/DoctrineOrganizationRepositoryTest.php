<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * EmailUniqueness checks the email before saving; when two requests take it at once, the unique index of
 * the database refuses the second one the same way.
 */
class DoctrineOrganizationRepositoryTest extends ApiTestCase
{
    public function testTheDatabaseRefusesAnEmailTakenMeanwhile(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $organizations->save($this->organizationWithTheEmail($prospector, 'contact@acme.test'));

        $this->expectException(EmailAlreadyUsed::class);

        $organizations->save($this->organizationWithTheEmail($prospector, 'contact@acme.test'));
    }

    public function testAnotherAccountMayProspectTheSameOrganization(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $organizations->save($this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@acme.test'));

        $organizations->save($this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@acme.test'));

        $this->addToAssertionCount(1);
    }

    private function organizationWithTheEmail(Prospector $prospector, string $email): Organization
    {
        return Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity(), new ContactDetails(new Email($email)), new \DateTimeImmutable());
    }
}
