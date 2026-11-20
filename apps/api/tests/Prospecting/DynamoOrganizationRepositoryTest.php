<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoOrganizationRepository;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\LinkedInPageAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\LinkedInPage;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Siret;
use Tests\App\Base\ApiTestCase;

/**
 * EmailUniqueness and SiretUniqueness check the email and the SIRET before saving; when two requests take one at
 * once, the conditional write of its reservation refuses the second one the same way (ADR 0039, 0048). The other questions are answered
 * by the items the repository keeps next to the organizations.
 */
class DynamoOrganizationRepositoryTest extends ApiTestCase
{
    private const string A_SIRET = '55210055400013';
    private const string ANOTHER_SIRET = '55210055400021';
    private const string A_PAGE = 'https://www.linkedin.com/company/acme-corp';
    private const string A_SITE = 'https://www.acme.fr';

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

        $acme->changeContactDetails(new ContactDetails(new Email('hello@acme.test')), null, null, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);

        $this->assertFalse($organizations->isEmailUsed($prospector->accountId, new Email('contact@acme.test'), except: new OrganizationId(self::newIdentity())));
        $this->assertTrue($organizations->isEmailUsed($prospector->accountId, new Email('hello@acme.test'), except: new OrganizationId(self::newIdentity())));
    }

    public function testTheOrganizationsHavingEmailsAreFoundByTheirReservations(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $organizations->save($acme);
        $organizations->save($this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@globex.test'));

        $found = $organizations->havingEmails($prospector->accountId, [new Email('contact@acme.test'), new Email('contact@globex.test'), new Email('nobody@acme.test')]);

        $this->assertEquals(['contact@acme.test' => $acme->id()], $found);
    }

    public function testTheOrganizationsNamingAContactAreFoundInItsAccount(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
        $ada = new PersonId(self::newIdentity());
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $acme->addContact($ada, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);
        $organizations->save($this->organizationWithTheEmail($prospector, 'contact@globex.test'));

        $this->assertSame([(string) $acme->id()], array_map(static fn (Organization $organization): string => (string) $organization->id(), $organizations->withContacts([$ada], $prospector->accountId)));
        $this->assertSame([], $organizations->withContacts([$ada], new AccountId(self::newIdentity())));

        $acme->removeContact($ada, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS), orphans: new OrphanContacts($organizations));
        $organizations->save($acme);

        $this->assertSame([], $organizations->withContacts([$ada], $prospector->accountId));
    }

    public function testTheSubsidiariesOfAHoldingAreFound(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $holding = $this->organizationWithTheEmail($prospector, 'contact@group.test');
        $subsidiary = $this->organizationWithTheEmail($prospector, 'contact@subsidiary.test');
        $subsidiary->attachToHolding($holding, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($holding);
        $organizations->save($subsidiary);

        $this->assertSame([(string) $subsidiary->id()], array_map(static fn (Organization $organization): string => (string) $organization->id(), $organizations->subsidiariesOf($holding->id())));
    }

    public function testTheOrganizationHoldingASiretIsFoundByIt(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationWithTheSiret($prospector, self::A_SIRET);
        $organizations->save($acme);

        $this->assertEquals($acme->id(), $organizations->withSiret($prospector->accountId, new Siret(self::A_SIRET), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testAnOrganizationIsNotFoundByItsOwnSiret(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheSiret(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_SIRET);
        $organizations->save($acme);

        $this->assertNull($organizations->withSiret($acme->accountId(), new Siret(self::A_SIRET), except: $acme->id()));
    }

    public function testTheReservationRefusesASiretTakenMeanwhile(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $organizations->save($this->organizationWithTheSiret($prospector, self::A_SIRET));

        $this->expectException(SiretAlreadyUsed::class);

        $organizations->save($this->organizationWithTheSiret($prospector, self::A_SIRET));
    }

    public function testANewSiretFreesTheFormerOne(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationWithTheSiret($prospector, self::A_SIRET);
        $organizations->save($acme);

        $acme->reviseLegalIdentity(new LegalIdentity('ACME', siret: new Siret(self::ANOTHER_SIRET)), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);

        $this->assertNull($organizations->withSiret($prospector->accountId, new Siret(self::A_SIRET), except: new OrganizationId(self::newIdentity())));
        $this->assertEquals($acme->id(), $organizations->withSiret($prospector->accountId, new Siret(self::ANOTHER_SIRET), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testAnOrganizationNoLongerProspectedFreesItsSiret(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheSiret(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_SIRET);
        $organizations->save($acme);

        $organizations->remove($acme);

        $this->assertNull($organizations->withSiret($acme->accountId(), new Siret(self::A_SIRET), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationIsReadBackAsItWasSaved(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheEmail(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'contact@acme.test');
        $acme->releaseEvents();
        $organizations->save($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    public function testAnOrganizationIsReadBackWithItsSiret(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationWithTheSiret(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_SIRET);
        $acme->releaseEvents();
        $organizations->save($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    public function testTheOrganizationHoldingALinkedInPageIsFoundByIt(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        $organizations->save($acme);

        $this->assertEquals($acme->id(), $organizations->withLinkedInPage($prospector->accountId, new LinkedInPage(self::A_PAGE), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testTheReservationRefusesALinkedInPageTakenMeanwhile(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $organizations->save($this->organizationOnTheWeb($prospector, self::A_PAGE, null));

        $this->expectException(LinkedInPageAlreadyUsed::class);

        $organizations->save($this->organizationOnTheWeb($prospector, self::A_PAGE, null));
    }

    public function testAnOrganizationNoLongerProspectedFreesItsLinkedInPage(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationOnTheWeb(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_PAGE, null);
        $organizations->save($acme);

        $organizations->remove($acme);

        $this->assertNull($organizations->withLinkedInPage($acme->accountId(), new LinkedInPage(self::A_PAGE), except: new OrganizationId(self::newIdentity())));
    }

    public function testTheOrganizationHoldingAWebsiteIsFoundByAnotherAddressOfItsDomain(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, null, self::A_SITE);
        $organizations->save($acme);

        $this->assertEquals($acme->id(), $organizations->withWebsiteOfDomain($prospector->accountId, new Website('https://acme.fr/blog'), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testANewWebsiteFreesTheDomainOfTheFormerOne(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, null, self::A_SITE);
        $organizations->save($acme);

        $acme->changeContactDetails(new ContactDetails(), null, new Website('https://acme.com'), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);

        $this->assertNull($organizations->withWebsiteOfDomain($prospector->accountId, new Website(self::A_SITE), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationNoLongerProspectedFreesTheDomainOfItsWebsite(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationOnTheWeb(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), null, self::A_SITE);
        $organizations->save($acme);

        $organizations->remove($acme);

        $this->assertNull($organizations->withWebsiteOfDomain($acme->accountId(), new Website(self::A_SITE), except: new OrganizationId(self::newIdentity())));
    }

    public function testTheKeysWrittenAgainReserveTheLinkedInPageOfAnOrganization(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->prospectingTable()->batch([], [[sprintf('LINKEDIN_PAGE#%s#%s', $prospector->accountId->value, self::A_PAGE), 'LINKEDIN_PAGE']]);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertEquals($acme->id(), self::getContainer()->get(OrganizationRepository::class)->withLinkedInPage($prospector->accountId, new LinkedInPage(self::A_PAGE), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testTheKeysWrittenAgainReserveTheDomainOfTheWebsiteOfAnOrganization(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, null, self::A_SITE);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->prospectingTable()->batch([], [[sprintf('WEBSITE#%s#acme.fr', $prospector->accountId->value), 'WEBSITE']]);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertEquals($acme->id(), self::getContainer()->get(OrganizationRepository::class)->withWebsiteOfDomain($prospector->accountId, new Website(self::A_SITE), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testAnOrganizationIsReadBackWithItsLinkedInPageAndItsWebsite(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationOnTheWeb(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_PAGE, self::A_SITE);
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

    public function testTheOrganizationsResemblingAnotherAreFoundByTheirKey(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        $organizations->save($acme);

        $this->assertEquals([$acme->id()], $organizations->resembling($prospector->accountId, $this->organizationIn($prospector, 'ACME', 'Lyon')->resemblanceKey(), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationNoLongerProspectedResemblesNoOtherOne(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        $organizations->save($acme);

        $organizations->remove($acme);

        $this->assertSame([], $organizations->resembling($prospector->accountId, $acme->resemblanceKey(), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationMovedNoLongerResemblesThoseOfItsFormerCity(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        $organizations->save($acme);
        $formerKey = $acme->resemblanceKey();

        $acme->relocateTo(new Address(city: 'Marseille'), null, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);

        $this->assertSame([], $organizations->resembling($prospector->accountId, $formerKey, except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationResemblingAnotherIsReadBackWithItsPossibleDuplicates(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        $acme->suspectDuplicateOf(new OrganizationId(self::newIdentity()), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $acme->releaseEvents();
        $organizations->save($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    public function testTheKeysWrittenAgainLinkAnOrganizationToThoseResemblingIt(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->prospectingTable()->batch([], [[sprintf('RESEMBLANCE#%s#%s', $prospector->accountId->value, $acme->resemblanceKey()), 'ORGANIZATION#'.$acme->id()]]);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertEquals([$acme->id()], self::getContainer()->get(OrganizationRepository::class)->resembling($prospector->accountId, $acme->resemblanceKey(), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationIsReadBackWithTheOrganizationsItIsNotADuplicateOf(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationIn($prospector, 'Acme SAS', 'Lyon');
        $acme->knowItIsNoDuplicateOf(new OrganizationId(self::newIdentity()), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $acme->releaseEvents();
        $organizations->save($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    private function prospectingTable(): Table
    {
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    private function organizationOnTheWeb(Prospector $prospector, ?string $page, ?string $site): Organization
    {
        $organization = Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity('ACME'), new ContactDetails(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organization->changeContactDetails(new ContactDetails(), LinkedInPage::orNone($page), Website::orNone($site), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));

        return $organization;
    }

    private function organizationIn(Prospector $prospector, string $corporateName, string $city): Organization
    {
        $organization = Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organization->relocateTo(new Address(city: $city), null, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));

        return $organization;
    }

    private function organizationWithTheSiret(Prospector $prospector, string $siret): Organization
    {
        return Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity('ACME', siret: new Siret($siret)), new ContactDetails(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
    }

    private function organizationWithTheEmail(Prospector $prospector, string $email): Organization
    {
        return Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity(), new ContactDetails(new Email($email)), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
    }
}
