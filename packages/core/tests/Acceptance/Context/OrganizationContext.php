<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Application\Organization\AttachToHoldingRequest;
use Prospecter\Core\Application\Organization\ChangeContactDetailsRequest;
use Prospecter\Core\Application\Organization\ChangeLogoRequest;
use Prospecter\Core\Application\Organization\ConsultOrganizationRequest;
use Prospecter\Core\Application\Organization\DetachFromHoldingRequest;
use Prospecter\Core\Application\Organization\FoundOrganization;
use Prospecter\Core\Application\Organization\OrganizationResponse;
use Prospecter\Core\Application\Organization\RelocateRequest;
use Prospecter\Core\Application\Organization\RemoveLogoRequest;
use Prospecter\Core\Application\Organization\ReviseLegalIdentityRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsResponse;
use Prospecter\Core\Application\Organization\StartProspectingRequest;
use Prospecter\Core\Application\Organization\WriteObservationsRequest;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\File;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\InMemoryStorage;

final class OrganizationContext implements Context
{
    use SharesTheScenario;

    private ?OrganizationResponse $consulted = null;
    private ?SearchProspectedOrganizationsResponse $found = null;

    #[Given('l\'organisation :name fait partie de mes organisations')]
    public function anOrganizationOfMine(string $name): Organization
    {
        return $this->anOrganizationProspectedBy($name, $this->myOrganizationName());
    }

    #[Given('l\'organisation :name fait partie de mes organisations avec :')]
    public function anOrganizationOfMineWith(string $name, TableNode $table): void
    {
        $this->detail($this->anOrganizationOfMine($name), $table->getRowsHash());
    }

    #[Given('l\'organisation sans raison sociale :email fait partie de mes organisations')]
    public function anOrganizationOfMineWithoutCorporateName(string $email): void
    {
        $organization = Organization::prospectedBy($this->application->signedIn()->getOrganizationId(), $this->application->organizations->nextIdentity(), new LegalIdentity(), new ContactDetails(new Email($email)));
        $this->scenario->save($email, $organization);
    }

    #[Given('l\'organisation :name appartient à :owner')]
    public function anOrganizationProspectedBy(string $name, string $owner): Organization
    {
        $organization = Organization::prospectedBy($this->scenario->idOf($owner), $this->application->organizations->nextIdentity(), new LegalIdentity($name), new ContactDetails());

        return $this->scenario->save($name, $organization);
    }

    #[Given('l\'organisation :name appartient à :owner avec :')]
    public function anOrganizationProspectedByWith(string $name, string $owner, TableNode $table): void
    {
        $this->detail($this->anOrganizationProspectedBy($name, $owner), $table->getRowsHash());
    }

    #[Given(':count organisations font partie de mes organisations')]
    public function manyOrganizationsOfMine(int $count): void
    {
        foreach (range(1, $count) as $number) {
            $this->anOrganizationOfMine(sprintf('Organisation %02d', $number));
        }
    }

    #[Given(':name est rattachée à la holding :holding')]
    public function anOrganizationIsAttachedTo(string $name, string $holding): void
    {
        $organization = $this->scenario->organizationNamed($name);
        $organization->attachToHolding($this->scenario->idOf($holding));
        $this->application->organizations->save($organization);
    }

    #[Given('j\'ai écrit les observations de :name : :observations')]
    public function iWroteObservations(string $name, string $observations): void
    {
        $this->iWriteObservations($name, $observations);
    }

    #[When('je commence à prospecter l\'organisation :')]
    public function iStartProspecting(TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $response = $this->application->startProspecting(new StartProspectingRequest(
            legalIdentity: Vocabulary::legalIdentity($values),
            contactDetails: Vocabulary::contactDetails($values),
            address: Vocabulary::address($values),
            observations: $values['observations'],
            holdingId: null === $values['holding'] ? null : $this->scenario->idOf($values['holding']),
        ));
        $this->rememberNameOf($response);
    }

    #[When('je révise l\'identité de :name :')]
    public function iReviseTheLegalIdentity(string $name, TableNode $table): void
    {
        $response = $this->application->reviseLegalIdentity(new ReviseLegalIdentityRequest($this->scenario->idOf($name), Vocabulary::legalIdentity(Vocabulary::values($table->getRowsHash()))));
        $this->rememberNameOf($response);
    }

    #[When('je change les coordonnées de :name :')]
    public function iChangeTheContactDetails(string $name, TableNode $table): void
    {
        $this->application->changeContactDetails(new ChangeContactDetailsRequest($this->scenario->idOf($name), Vocabulary::contactDetails(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je déménage :name à l\'adresse :')]
    public function iRelocate(string $name, TableNode $table): void
    {
        $this->application->relocate(new RelocateRequest($this->scenario->idOf($name), Vocabulary::address(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('j\'efface l\'adresse de :name')]
    public function iEraseTheAddress(string $name): void
    {
        $this->application->relocate(new RelocateRequest($this->scenario->idOf($name), null));
    }

    #[When('j\'écris les observations de :name : :observations')]
    public function iWriteObservations(string $name, string $observations): void
    {
        $this->application->writeObservations(new WriteObservationsRequest($this->scenario->idOf($name), $observations));
    }

    #[When('j\'efface les observations de :name')]
    public function iEraseTheObservations(string $name): void
    {
        $this->application->writeObservations(new WriteObservationsRequest($this->scenario->idOf($name), null));
    }

    #[When('je rattache :name à la holding :holding')]
    public function iAttachToHolding(string $name, string $holding): void
    {
        $this->application->attachToHolding(new AttachToHoldingRequest($this->scenario->idOf($name), $this->scenario->idOf($holding)));
    }

    #[When('je détache :name de sa holding')]
    public function iDetachFromHolding(string $name): void
    {
        $this->application->detachFromHolding(new DetachFromHoldingRequest($this->scenario->idOf($name)));
    }

    #[When('je change le logo de :name avec :fileName')]
    public function iChangeTheLogo(string $name, string $fileName): void
    {
        $this->application->changeLogo(new ChangeLogoRequest($this->scenario->idOf($name), $this->scenario->upload($fileName)));
    }

    #[When('je retire le logo de :name')]
    public function iRemoveTheLogo(string $name): void
    {
        $this->application->removeLogo(new RemoveLogoRequest($this->scenario->idOf($name)));
    }

    #[When('je consulte l\'organisation :name')]
    public function iConsultTheOrganization(string $name): void
    {
        $this->consulted = $this->application->consultOrganization(new ConsultOrganizationRequest($this->scenario->idOf($name)));
    }

    #[When('je liste mes organisations')]
    public function iListMyOrganizations(): void
    {
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest());
    }

    #[When('je cherche :query dans mes organisations')]
    public function iSearchMyOrganizations(string $query): void
    {
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest(query: $query));
    }

    #[When('je liste la page :page de mes organisations, :maxByPage par page')]
    public function iListAPageOfMyOrganizations(int $page, int $maxByPage): void
    {
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest($page, '', $maxByPage));
    }

    #[Then('l\'organisation :name est enregistrée avec :')]
    public function theOrganizationIsStoredWith(string $name, TableNode $table): void
    {
        Expect::describes($table->getRowsHash(), Vocabulary::describeOrganization($this->scenario->organizationNamed($name), $this->scenario->nameOf(...)));
    }

    #[Then('l\'organisation :name compte :count organisation(s) active(s)')]
    public function theOrganizationCountsActiveOrganizations(string $name, int $count): void
    {
        Expect::same($count, $this->application->organizations->countProspectedBy($this->scenario->idOf($name)));
    }

    #[Then('je vois l\'organisation :')]
    public function iSeeTheOrganization(TableNode $table): void
    {
        Expect::that(null !== $this->consulted, sprintf('The organization was expected, got: %s', $this->application->refusal()?->getMessage()));
        $organization = $this->consulted;

        Expect::describes($table->getRowsHash(), [
            'raison sociale' => (string) $organization->legalIdentity->corporateName,
            'forme juridique' => (string) $organization->legalIdentity->form,
            'e-mail' => (string) $organization->contactDetails->email,
            'ville' => (string) $organization->address?->city,
            'position' => Vocabulary::describeGeoPoint($organization->geoPoint),
            'logo' => null === $organization->logo ? 'aucun' : basename($organization->logo->url),
            'organisations actives' => (string) $organization->activeOrganizations,
        ]);
    }

    #[Then('je trouve :total organisation(s) sur :pages page(s)')]
    public function iFindOrganizationsOnPages(int $total, int $pages): void
    {
        Expect::that(null !== $this->found, sprintf('Organizations were expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same([$total, $pages], [$this->found->total, $this->found->totalPages]);
    }

    #[Then('la liste affiche :')]
    public function theListShows(TableNode $table): void
    {
        Expect::that(null !== $this->found, 'Organizations were expected');
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $shown = array_map(static fn (FoundOrganization $organization): array => array_intersect_key([
            'nom' => $organization->fullName,
            'ville' => (string) $organization->address?->city,
        ], $columns), $this->found->organizations);

        Expect::same($table->getColumnsHash(), $shown);
    }

    #[Then('le stockage contient :fileName')]
    public function theStorageContains(string $fileName): void
    {
        Expect::that($this->application->storage->has(InMemoryStorage::urlOf($fileName)), sprintf('"%s" is not stored', $fileName));
    }

    #[Then('le stockage ne contient plus :fileName')]
    public function theStorageNoLongerContains(string $fileName): void
    {
        Expect::that(!$this->application->storage->has(InMemoryStorage::urlOf($fileName)), sprintf('"%s" is still stored', $fileName));
    }

    private function myOrganizationName(): string
    {
        return $this->scenario->nameOf($this->application->signedIn()->getOrganizationId());
    }

    private function rememberNameOf(?OrganizationResponse $response): void
    {
        if (null !== $response) {
            $this->scenario->remember($response->legalIdentity->corporateName, $response->id);
        }
    }

    /**
     * @param array<string, string> $details
     */
    private function detail(Organization $organization, array $details): void
    {
        $values = Vocabulary::values(array_diff_key($details, ['logo' => true]));
        $organization->reviseLegalIdentity(new LegalIdentity($organization->legalIdentity()->corporateName, $values['forme juridique']));
        $organization->changeContactDetails(Vocabulary::contactDetails($values));
        $address = Vocabulary::address($values);
        $organization->relocateTo($address, null === $address ? null : $this->application->geoLocation->find((string) $address));

        if (isset($details['logo'])) {
            $organization->changeLogo($this->storedImage($details['logo']));
        }

        $this->application->organizations->save($organization);
    }

    private function storedImage(string $fileName): File
    {
        $this->scenario->iHaveAnImage($fileName, 1024);

        return new File($this->application->storage->add($this->scenario->upload($fileName)), 'png', 1024);
    }
}
