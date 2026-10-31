<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * EmailUniqueness checks the email before saving; when two requests take it at once, the conditional write
 * of its reservation refuses the second one the same way (ADR 0039, 0048). The other questions are answered
 * by the items the repository keeps next to the organizations.
 */
class DynamoOrganizationRepositoryTest extends ApiTestCase
{
    public function testTheReservationRefusesAnEmailTakenMeanwhile(): void
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

    public function testANewEmailFreesTheFormerOne(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $organizations->save($acme);

        $acme->changeContactDetails(new ContactDetails(new Email('hello@acme.test')), by: $prospector->id, at: new \DateTimeImmutable());
        $organizations->save($acme);

        $this->assertFalse($organizations->isEmailUsed($prospector->accountId, new Email('contact@acme.test'), except: new OrganizationId(self::newIdentity())));
        $this->assertTrue($organizations->isEmailUsed($prospector->accountId, new Email('hello@acme.test'), except: new OrganizationId(self::newIdentity())));
    }

    public function testTheOrganizationsNamingAContactAreFoundInItsAccount(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
        $ada = new PersonId(self::newIdentity());
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $acme->addContact($ada, by: $prospector->id, at: new \DateTimeImmutable());
        $organizations->save($acme);
        $organizations->save($this->organizationWithTheEmail($prospector, 'contact@globex.test'));

        $this->assertSame([(string) $acme->id()], array_map(static fn (Organization $organization): string => (string) $organization->id(), $organizations->withContacts([$ada], $prospector->accountId)));
        $this->assertSame([], $organizations->withContacts([$ada], new AccountId(self::newIdentity())));

        $acme->removeContact($ada, by: $prospector->id, at: new \DateTimeImmutable(), orphans: new OrphanContacts($organizations));
        $organizations->save($acme);

        $this->assertSame([], $organizations->withContacts([$ada], $prospector->accountId));
    }

    public function testTheSubsidiariesOfAHoldingAreFound(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $holding = $this->organizationWithTheEmail($prospector, 'contact@group.test');
        $subsidiary = $this->organizationWithTheEmail($prospector, 'contact@subsidiary.test');
        $subsidiary->attachToHolding($holding, by: $prospector->id, at: new \DateTimeImmutable());
        $organizations->save($holding);
        $organizations->save($subsidiary);

        $this->assertSame([(string) $subsidiary->id()], array_map(static fn (Organization $organization): string => (string) $organization->id(), $organizations->subsidiariesOf($holding->id())));
    }

    public function testAnOrganizationIsReadBackAsItWasSaved(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@acme.test');
        $acme->releaseEvents();
        $organizations->save($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    public function testTheOrganizationsThatExistAmongIdsAreReadInOneBatch(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@acme.test');
        $organizations->save($acme);

        $found = $organizations->ofIds([$acme->id(), new OrganizationId(self::newIdentity())]);

        $this->assertEquals([$acme->id()], array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $found));
    }

    private function organizationWithTheEmail(Prospector $prospector, string $email): Organization
    {
        return Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity(), new ContactDetails(new Email($email)), new \DateTimeImmutable());
    }
}
