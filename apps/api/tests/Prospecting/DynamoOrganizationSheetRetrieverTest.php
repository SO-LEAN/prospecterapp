<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
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
        $acme->changeLogo(new File('https://static.test/acme.png', 'png', 1024), $prospector->id, new \DateTimeImmutable());
        $acme->attachToHolding($this->prospect($prospector, 'Groupe ACME'), $prospector->id, new \DateTimeImmutable());
        $acme->addContact(new PersonId(self::newIdentity()), $prospector->id, new \DateTimeImmutable());
        $this->organizations()->save($acme);

        $this->assertSame(self::OFFERED_BY_THE_STATE, array_values(array_intersect(self::OFFERED_BY_THE_STATE, $this->sheetOf($acme)->possibleIntents)));
    }

    private function prospect(Prospector $prospector, string $corporateName): Organization
    {
        $organization = Organization::prospectedBy($prospector, new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails(), new \DateTimeImmutable());
        $this->organizations()->save($organization);

        return $organization;
    }

    private function sheetOf(Organization $organization): OrganizationSheet
    {
        return self::getContainer()->get(OrganizationSheetRetriever::class)->get($organization->id());
    }

    private function organizations(): OrganizationRepository
    {
        return self::getContainer()->get(OrganizationRepository::class);
    }

    private function aProspector(): Prospector
    {
        return new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
    }
}
