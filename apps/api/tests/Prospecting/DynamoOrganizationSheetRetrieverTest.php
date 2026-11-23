<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\WebPresence;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Location\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Location\Location;
use Tests\App\Base\ApiTestCase;

/**
 * The retriever builds the sheet from the item the repository wrote, with what its state offers: the
 * intentions its state forbids are absent, the aggregate refusing them (ADR 0088).
 */
class DynamoOrganizationSheetRetrieverTest extends ApiTestCase
{
    private const array OFFERED_BY_THE_STATE = [OrganizationIntent::RemoveLogo->name, OrganizationIntent::DetachFromHolding->name, OrganizationIntent::RemoveContact->name];

    public function testABareOrganizationOffersNeitherToRemoveALogoNorToDetachFromAHoldingNorToRemoveAContact(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');

        $possibleIntents = $this->sheetOf($acme)->possibleIntents;

        $this->assertSame([], array_values(array_intersect(self::OFFERED_BY_THE_STATE, $possibleIntents)));
        $this->assertContains(OrganizationIntent::ChangeLogo->name, $possibleIntents);
    }

    public function testAnOrganizationWithALogoAHoldingAndAContactOffersToRemoveThem(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');
        $acme->changeLogo(new File('https://static.test/acme.png', 'png', 1024), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $acme->attachToHolding($this->prospect($prospector, 'Groupe ACME'), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $acme->addContact(new ContactId(self::newIdentity()), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->organizations()->save($acme);

        $this->assertSame(self::OFFERED_BY_THE_STATE, array_values(array_intersect(self::OFFERED_BY_THE_STATE, $this->sheetOf($acme)->possibleIntents)));
    }

    public function testTheSheetCarriesTheSiret(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');
        $acme->reviseLegalIdentity(new LegalIdentity('ACME', siret: new Siret('55210055400013')), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->organizations()->save($acme);

        $this->assertSame('55210055400013', $this->sheetOf($acme)->siret);
    }

    public function testTheSheetCarriesTheMunicipality(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');
        $acme->relocateTo(new Address('17 rue Docteur Bouchut', '69003', 'Lyon'), new Location(new GeoPoint(4.86, 45.76), '69383'), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->organizations()->save($acme);

        $this->assertSame('69383', $this->sheetOf($acme)->municipalityCode);
    }

    public function testTheSheetCarriesTheLinksAndTheWebsite(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');
        $acme->changeContactDetails(new ContactDetails(), new WebPresence(ExternalReferences::designating(['linkedin.com/company/ACME-Corp'], Designated::Organization), new Website('acme.fr')), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->organizations()->save($acme);

        $sheet = $this->sheetOf($acme);

        $this->assertEquals([[new ExternalLink('linkedInCompany', 'acme-corp', 'https://linkedin.com/company/ACME-Corp')], 'https://acme.fr'], [$sheet->links, $sheet->website]);
    }

    public function testTheSheetOfAnOrganizationResemblingOthersNamesThoseStillProspected(): void
    {
        $prospector = $this->aProspector();
        $acme = $this->prospect($prospector, 'ACME');
        $duplicate = $this->prospect($prospector, 'Acme', 'SAS');
        $acme->suspectDuplicateOf($duplicate->id(), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $acme->suspectDuplicateOf(new OrganizationId(self::newIdentity()), $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->organizations()->save($acme);

        $this->assertEquals([new PossibleDuplicate($duplicate->id()->value, 'Acme SAS')], $this->sheetOf($acme)->possibleDuplicates);
    }

    private function prospect(Prospector $prospector, string $corporateName, ?string $form = null): Organization
    {
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName, $form), new ContactDetails());
        $this->organizations()->save($organization);

        return $organization;
    }

    private function sheetOf(Organization $organization): OrganizationSheet
    {
        return self::service(OrganizationSheetRetriever::class)->get($organization->id());
    }

    private function organizations(): OrganizationRepository
    {
        return self::service(OrganizationRepository::class);
    }

    private function aProspector(): Prospector
    {
        return new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
    }
}
