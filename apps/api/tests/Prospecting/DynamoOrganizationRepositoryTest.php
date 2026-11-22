<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoOrganizationRepository;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\WebPresence;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
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
    private const array STORED_UNDER_ITS_HOST = ['source' => 'linkedin.com', 'identifier' => 'linkedin.com/company/acme-corp', 'address' => self::A_PAGE];

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

        $acme->changeContactDetails(new ContactDetails(new Email('hello@acme.test')), new WebPresence(ExternalReferences::none()), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
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

    public function testAnOrganizationAnAiStartedIsReadBackCreatedByAnAi(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = Organization::prospectedBy(new Gesture(new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-10-18 19:00 UTC'), Origin::Ai), new OrganizationId(self::newIdentity()), new LegalIdentity('ACME'), new ContactDetails());
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

    public function testTheOrganizationHoldingALinkIsFoundByAnotherAddressOfItsReference(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        $organizations->save($acme);

        $this->assertEquals($acme->id(), $organizations->withReference($prospector->accountId, self::reference('fr.linkedin.com/company/ACME-Corp/about'), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testTheReservationRefusesALinkTakenMeanwhile(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $organizations->save($this->organizationOnTheWeb($prospector, self::A_PAGE, null));

        $this->expectException(ReferenceAlreadyUsed::class);

        $organizations->save($this->organizationOnTheWeb($prospector, self::A_PAGE, null));
    }

    public function testALinkDroppedIsFreed(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        $organizations->save($acme);

        $acme->changeContactDetails(new ContactDetails(), new WebPresence(ExternalReferences::none()), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($acme);

        $this->assertNull($organizations->withReference($prospector->accountId, self::reference(self::A_PAGE), except: new OrganizationId(self::newIdentity())));
    }

    public function testAnOrganizationNoLongerProspectedFreesItsLinks(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationOnTheWeb(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_PAGE, null);
        $organizations->save($acme);

        $organizations->remove($acme);

        $this->assertNull($organizations->withReference($acme->accountId(), self::reference(self::A_PAGE), except: new OrganizationId(self::newIdentity())));
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

        $acme->changeContactDetails(new ContactDetails(), new WebPresence(ExternalReferences::none(), new Website('https://acme.com')), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
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

    public function testTheKeysWrittenAgainReserveTheLinksOfAnOrganization(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->prospectingTable()->batch([], [[sprintf('REFERENCE#%s#linkedInCompany#acme-corp', $prospector->accountId->value), 'REFERENCE']]);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertEquals($acme->id(), self::getContainer()->get(OrganizationRepository::class)->withReference($prospector->accountId, self::reference(self::A_PAGE), except: new OrganizationId(self::newIdentity()))?->id());
    }

    public function testTheKeysWrittenAgainTakeTheLinkedInPageOfAnOrganizationWrittenBeforeTheLinksAsOneOfThem(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->writtenBeforeTheLinks($acme);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertEquals($acme->id(), self::getContainer()->get(OrganizationRepository::class)->withReference($prospector->accountId, self::reference(self::A_PAGE), except: new OrganizationId(self::newIdentity()))?->id());
        $this->assertNull($this->prospectingTable()->item(sprintf('LINKEDIN_PAGE#%s#%s', $prospector->accountId->value, self::A_PAGE), 'LINKEDIN_PAGE'));
    }

    public function testAnOrganizationWrittenBeforeTheLinksIsReadWithItsLinkedInPageAsOneOfThem(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $acme = $this->organizationOnTheWeb(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), self::A_PAGE, null);
        $acme->releaseEvents();
        $organizations->save($acme);
        $this->writtenBeforeTheLinks($acme);

        $this->assertEquals($acme, $organizations->get($acme->id()));
    }

    public function testALinkIsReadAsItWasStoredThoughItsAddressIsRecognizedOtherwiseToday(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->storedWithTheLink($acme, self::STORED_UNDER_ITS_HOST);

        $this->assertSame([['linkedin.com', 'linkedin.com/company/acme-corp']], self::sourcesAndIdentifiersOf(self::getContainer()->get(OrganizationRepository::class)->get($acme->id())));
    }

    public function testTheKeysWrittenAgainRecognizeAgainALinkStoredUnderTheHostOfASourceKnownSince(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->storedWithTheLink($acme, self::STORED_UNDER_ITS_HOST);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertSame([['linkedInCompany', 'acme-corp']], self::sourcesAndIdentifiersOf(self::getContainer()->get(OrganizationRepository::class)->get($acme->id())));
        $this->assertEquals($acme->id(), self::getContainer()->get(OrganizationRepository::class)->withReference($prospector->accountId, self::reference(self::A_PAGE), except: new OrganizationId(self::newIdentity()))?->id());
        $this->assertNull($this->prospectingTable()->item(sprintf('REFERENCE#%s#linkedin.com#linkedin.com/company/acme-corp', $prospector->accountId->value), 'REFERENCE'));
    }

    public function testTheKeysWrittenAgainKeepAsItWasStoredALinkRefusedToday(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $acme = $this->organizationOnTheWeb($prospector, self::A_PAGE, null);
        self::getContainer()->get(OrganizationRepository::class)->save($acme);
        $this->storedWithTheLink($acme, ['source' => 'linkedin.com', 'identifier' => 'linkedin.com/in/ada', 'address' => 'https://www.linkedin.com/in/ada']);

        self::getContainer()->get(DynamoOrganizationRepository::class)->writeTheKeysAgain();

        $this->assertSame([['linkedin.com', 'linkedin.com/in/ada']], self::sourcesAndIdentifiersOf(self::getContainer()->get(OrganizationRepository::class)->get($acme->id())));
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

    public function testAnOrganizationIsReadBackWithItsLinksAndItsWebsite(): void
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

    public function testAnOrganizationChangedSinceItWasReadIsNotSavedOverTheChange(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $organizations->save($acme);
        $grace = new PersonId(self::newIdentity());
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $organizations->get($acme->id());
        $this->anotherRequestAttaches($grace, $acme->id(), $prospector);
        $read->addContact(new PersonId(self::newIdentity()), by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($read);

        try {
            $unitOfWork->commit();
            $this->fail('The save was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertTrue($organizations->get($acme->id())->hasContact($grace), 'the contact attached meanwhile stays');
    }

    public function testAnOrganizationChangedSinceItWasReadIsNotRemovedOverTheChange(): void
    {
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
        $acme = $this->organizationWithTheEmail($prospector, 'contact@acme.test');
        $organizations->save($acme);
        $grace = new PersonId(self::newIdentity());
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $organizations->get($acme->id());
        $this->anotherRequestAttaches($grace, $acme->id(), $prospector);
        $organizations->remove($read);

        try {
            $unitOfWork->commit();
            $this->fail('The removal was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertTrue($organizations->get($acme->id())->hasContact($grace), 'the organization stays, with the contact attached meanwhile');
    }

    private function anotherRequestAttaches(PersonId $contact, OrganizationId $organization, Prospector $prospector): void
    {
        $organizations = new DynamoOrganizationRepository($this->asAnotherRequest($this->prospectingTable()));
        $attaching = $organizations->get($organization);
        $attaching->addContact($contact, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $organizations->save($attaching);
    }

    private function prospectingTable(): Table
    {
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    private function organizationOnTheWeb(Prospector $prospector, ?string $page, ?string $site): Organization
    {
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity('ACME'), new ContactDetails());
        $organization->changeContactDetails(new ContactDetails(), new WebPresence(ExternalReferences::designating(array_filter([$page]), Designated::Organization), Website::orNone($site)), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));

        return $organization;
    }

    /**
     * Its document and its reservation as if its link had been noted before its source was known, or under another
     * rule than today's.
     *
     * @param array{source: string, identifier: string, address: string} $link
     */
    private function storedWithTheLink(Organization $organization, array $link): void
    {
        $item = $this->prospectingTable()->item(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $item['organization']['references'] = [$link];
        $reservation = sprintf('REFERENCE#%s#%s#%s', $organization->accountId()->value, $link['source'], $link['identifier']);
        $this->prospectingTable()->batch([$item, ['PK' => $reservation, 'SK' => 'REFERENCE', 'ownerId' => $organization->id()->value]], [[sprintf('REFERENCE#%s#linkedInCompany#acme-corp', $organization->accountId()->value), 'REFERENCE']]);
    }

    /**
     * @return list<array{string, string}>
     */
    private static function sourcesAndIdentifiersOf(Organization $organization): array
    {
        return array_map(static fn (ExternalReference $reference): array => [$reference->source, $reference->identifier], $organization->references()->all());
    }

    private static function reference(string $address): ExternalReference
    {
        return ExternalReference::designating($address, Designated::Organization);
    }

    /**
     * Its document and its reservation as they were written before the links: its LinkedIn page alone.
     */
    private function writtenBeforeTheLinks(Organization $organization): void
    {
        $key = OrganizationKeys::organization($organization->id()->value);
        $item = $this->prospectingTable()->item($key, OrganizationKeys::ORGANIZATION);
        $page = $item['organization']['references'][0]['address'];
        unset($item['organization']['references']);
        $item['organization']['linkedInPage'] = $page;
        $reservation = sprintf('LINKEDIN_PAGE#%s#%s', $organization->accountId()->value, $page);
        $this->prospectingTable()->batch([$item, ['PK' => $reservation, 'SK' => 'LINKEDIN_PAGE', 'ownerId' => $organization->id()->value]], [[sprintf('REFERENCE#%s#linkedInCompany#acme-corp', $organization->accountId()->value), 'REFERENCE']]);
    }

    private function organizationIn(Prospector $prospector, string $corporateName, string $city): Organization
    {
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails());
        $organization->relocateTo(new Address(city: $city), null, by: $prospector->id, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));

        return $organization;
    }

    private function organizationWithTheSiret(Prospector $prospector, string $siret): Organization
    {
        return Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity('ACME', siret: new Siret($siret)), new ContactDetails());
    }

    private function organizationWithTheEmail(Prospector $prospector, string $email): Organization
    {
        return Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity(), new ContactDetails(new Email($email)));
    }
}
