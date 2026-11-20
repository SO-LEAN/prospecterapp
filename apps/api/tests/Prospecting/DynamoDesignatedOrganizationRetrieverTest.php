<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoDesignatedOrganizationRetriever;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\Prospecting\Application\Port\DesignatedOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Siret;
use Tests\App\Base\ApiTestCase;

/**
 * The organization of an account a key designates, read in the reservations the repository writes, without
 * restoring it (ADR 0088).
 */
class DynamoDesignatedOrganizationRetrieverTest extends ApiTestCase
{
    private const string SIRET = '55210055400013';
    private const string PAGE = 'https://www.linkedin.com/company/acme-corp';

    public function testASiretDesignatesTheOrganizationOfTheAccountHavingIt(): void
    {
        $prospector = self::aProspector();
        $acme = $this->prospect($prospector, new LegalIdentity('ACME', siret: new Siret(self::SIRET)));

        $this->assertSame($acme->id()->value, $this->retriever()->withSiret($prospector->accountId, new Siret(self::SIRET))?->value);
    }

    public function testALinkDesignatesTheOrganizationOfTheAccountHoldingIt(): void
    {
        $prospector = self::aProspector();
        $acme = $this->prospect($prospector, new LegalIdentity('ACME'));
        $this->changeContactDetails($acme, $prospector, ExternalReferences::designating([self::PAGE], Designated::Organization));

        $this->assertSame($acme->id()->value, $this->retriever()->withReference($prospector->accountId, ExternalReference::designating(self::PAGE, Designated::Organization))?->value);
    }

    public function testTheDomainOfAWebsiteDesignatesTheOrganizationOfTheAccountHavingIt(): void
    {
        $prospector = self::aProspector();
        $acme = $this->prospect($prospector, new LegalIdentity('ACME'));
        $this->changeContactDetails($acme, $prospector, ExternalReferences::none(), new Website('https://www.acme.test'));

        $this->assertSame($acme->id()->value, $this->retriever()->withWebsiteDomain($prospector->accountId, 'acme.test')?->value);
    }

    public function testAnEmailDesignatesTheOrganizationOfTheAccountHavingIt(): void
    {
        $prospector = self::aProspector();
        $acme = $this->prospect($prospector, new LegalIdentity('ACME'), new ContactDetails(new Email('contact@acme.test')));

        $this->assertSame($acme->id()->value, $this->retriever()->withEmail($prospector->accountId, new Email('contact@acme.test'))?->value);
    }

    public function testTheKeyOfAnOrganizationOfAnotherAccountDesignatesNone(): void
    {
        $this->prospect(self::aProspector(), new LegalIdentity('ACME', siret: new Siret(self::SIRET)));

        $this->assertNull($this->retriever()->withSiret(self::aProspector()->accountId, new Siret(self::SIRET)));
    }

    public function testTheOrganizationsAreNamedInTheOrderOfTheirIds(): void
    {
        $prospector = self::aProspector();
        $acme = $this->prospect($prospector, new LegalIdentity('ACME', 'SAS'));
        $globex = $this->prospect($prospector, new LegalIdentity(), new ContactDetails(new Email('contact@globex.test')));

        $named = $this->retriever()->named([$globex->id(), new OrganizationId(self::newIdentity()), $acme->id()]);

        $this->assertEquals([new Candidate($globex->id()->value, 'contact@globex.test'), new Candidate($acme->id()->value, 'ACME SAS')], $named);
    }

    /**
     * Built on the table of Prospecting: no use case asks it yet in the container.
     */
    private function retriever(): DesignatedOrganizationRetriever
    {
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);

        return new DynamoDesignatedOrganizationRetriever($table);
    }

    private static function aProspector(): Prospector
    {
        return new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
    }

    private function prospect(Prospector $prospector, LegalIdentity $legalIdentity, ContactDetails $contactDetails = new ContactDetails()): Organization
    {
        $organization = Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), $legalIdentity, $contactDetails, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::getContainer()->get(OrganizationRepository::class)->save($organization);

        return $organization;
    }

    private function changeContactDetails(Organization $organization, Prospector $prospector, ExternalReferences $references, ?Website $website = null): void
    {
        $organization->changeContactDetails(new ContactDetails(), $references, $website, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::getContainer()->get(OrganizationRepository::class)->save($organization);
    }
}
