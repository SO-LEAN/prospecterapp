<?php

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Prospecter\Core\Entity\Address;
use Prospecter\Core\Entity\File;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoRequest;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;
use Tests\Core\Acceptance\Application;
use Tests\Core\Acceptance\Vocabulary;

final class OrganizationContext implements Context
{
    private ScenarioContext $scenario;
    private Application $application;
    private ?GetOrganizationResponse $consulted = null;
    private ?FindMyOwnOrganizationsResponse $found = null;

    #[BeforeScenario]
    public function gatherScenario(BeforeScenarioScope $scope): void
    {
        $this->scenario = $scope->getEnvironment()->getContext(ScenarioContext::class);
        $this->application = $this->scenario->application;
    }

    #[Given('l\'organisation :name fait partie de mes organisations')]
    public function anOrganizationOfMine(string $name): Organization
    {
        return $this->anOrganizationOwnedBy($name, $this->scenario->me()->getOrganization()->getCorporateName());
    }

    #[Given('l\'organisation :name fait partie de mes organisations avec :')]
    public function anOrganizationOfMineWith(string $name, TableNode $table): void
    {
        $this->fill($this->anOrganizationOfMine($name), $table->getRowsHash());
    }

    #[Given('l\'organisation :name appartient à :owner avec :')]
    public function anOrganizationOwnedByWith(string $name, string $owner, TableNode $table): void
    {
        $this->fill($this->anOrganizationOwnedBy($name, $owner), $table->getRowsHash());
    }

    #[Given('l\'organisation :name appartient à :owner')]
    public function anOrganizationOwnedBy(string $name, string $owner): Organization
    {
        $organization = new Organization();
        $organization->setCorporateName($name);
        $organization->setOwnedBy($this->scenario->organizationNamed($owner));

        return $this->application->organizations->create($organization);
    }

    #[Given(':count organisations font partie de mes organisations')]
    public function manyOrganizationsOfMine(int $count): void
    {
        foreach (range(1, $count) as $number) {
            $this->anOrganizationOfMine(sprintf('Organisation %02d', $number));
        }
    }

    #[When('je crée l\'organisation :')]
    public function iCreateTheOrganization(TableNode $table): void
    {
        $this->create($table, $this->application->signedIn()->getOrganizationId());
    }

    #[When('je crée une organisation sans propriétaire :')]
    public function iCreateAnOrganizationWithoutOwner(TableNode $table): void
    {
        $this->create($table, null);
    }

    #[When('je consulte l\'organisation :name')]
    public function iConsultTheOrganization(string $name): void
    {
        $this->consulted = $this->application->getOrganization(new GetOrganizationRequest($this->scenario->idOf($name)));
    }

    #[When('je liste mes organisations')]
    public function iListMyOrganizations(): void
    {
        $this->found = $this->application->findMyOwnOrganizations(new FindMyOwnOrganizationsRequest(1, ''));
    }

    #[When('je cherche :query dans mes organisations')]
    public function iSearchMyOrganizations(string $query): void
    {
        $this->found = $this->application->findMyOwnOrganizations(new FindMyOwnOrganizationsRequest(1, $query));
    }

    #[When('je liste la page :page de mes organisations, :maxByPage par page')]
    public function iListAPageOfMyOrganizations(int $page, int $maxByPage): void
    {
        $this->found = $this->application->findMyOwnOrganizations(new FindMyOwnOrganizationsRequest($page, '', $maxByPage));
    }

    #[When('je modifie l\'organisation :name avec :')]
    public function iUpdateTheOrganization(string $name, TableNode $table): void
    {
        $this->application->updateOrganization(new UpdateOrganizationRequest(...['id' => $this->scenario->idOf($name), ...$this->requestParameters($table)]));
    }

    #[When('je retire le logo de :name')]
    public function iRemoveTheLogoOf(string $name): void
    {
        $this->application->removeOrganizationLogo(new RemoveOrganizationLogoRequest($this->scenario->idOf($name)));
    }

    #[Then('l\'organisation :name est enregistrée avec :')]
    public function theOrganizationIsStoredWith(string $name, TableNode $table): void
    {
        Vocabulary::assertDescribes($table->getRowsHash(), Vocabulary::describeOrganization($this->scenario->organizationNamed($name)));
    }

    #[Then('aucune organisation :name n\'est enregistrée')]
    public function noOrganizationIsStored(string $name): void
    {
        Assert::assertNull($this->application->organizations->findOneBy(['corporateName' => $name]));
    }

    #[Then('l\'organisation :name compte :count organisation(s) active(s)')]
    public function theOrganizationCountsActiveOrganizations(string $name, int $count): void
    {
        Assert::assertSame($count, $this->scenario->organizationNamed($name)->getStat('activeOrganizations'));
    }

    #[Then('je vois l\'organisation :')]
    public function iSeeTheOrganization(TableNode $table): void
    {
        Assert::assertNotNull($this->consulted, sprintf('the organization was expected, got: %s', $this->application->refusal()?->getMessage()));
        $response = $this->consulted;
        $longitude = Vocabulary::read($response, 'longitude');

        Vocabulary::assertDescribes($table->getRowsHash(), [
            'raison sociale' => (string) Vocabulary::read($response, 'corporateName'),
            'forme juridique' => (string) Vocabulary::read($response, 'form'),
            'e-mail' => (string) Vocabulary::read($response, 'email'),
            'ville' => (string) Vocabulary::read($response, 'city'),
            'position' => null === $longitude ? 'aucune' : sprintf('%s, %s', $longitude, Vocabulary::read($response, 'latitude')),
            'logo' => basename((string) Vocabulary::read($response, 'logoUrl')) ?: 'aucun',
            'organisations actives' => (string) (Vocabulary::read($response, 'stats')['activeOrganizations'] ?? 0),
        ]);
    }

    #[Then('je trouve :total organisation(s) sur :pages page(s)')]
    public function iFindOrganizationsOnPages(int $total, int $pages): void
    {
        Assert::assertNotNull($this->found);
        Assert::assertSame($total, Vocabulary::read($this->found, 'total'));
        Assert::assertSame($pages, Vocabulary::read($this->found, 'totalPages'));
    }

    #[Then('la liste affiche :')]
    public function theListShows(TableNode $table): void
    {
        Assert::assertNotNull($this->found);
        $shown = array_map(static fn (object $organization): array => [
            'nom' => Vocabulary::read($organization, 'fullName'),
            'ville' => (string) Vocabulary::read($organization, 'city'),
        ], Vocabulary::read($this->found, 'organizations'));

        Assert::assertSame($table->getColumnsHash(), array_map(
            static fn (array $row): array => array_intersect_key($row, array_flip(array_keys($table->getColumnsHash()[0] ?? []))),
            $shown,
        ));
    }

    #[Then('le stockage contient :fileName')]
    public function theStorageContains(string $fileName): void
    {
        Assert::assertTrue($this->application->storage->has($this->application->storage::urlOf($fileName)));
    }

    #[Then('le stockage ne contient plus :fileName')]
    public function theStorageNoLongerContains(string $fileName): void
    {
        Assert::assertFalse($this->application->storage->has($this->application->storage::urlOf($fileName)));
    }

    /**
     * @param array<string, string> $details
     */
    private function fill(Organization $organization, array $details): void
    {
        $organization->setEmail($details['e-mail'] ?? null);
        $organization->setForm($details['forme juridique'] ?? null);
        if (isset($details['ville'])) {
            $organization->setAddress(Address::fromValues(null, $details['code postal'] ?? null, $details['ville'], $details['pays'] ?? null));
        }
        if (isset($details['logo'])) {
            $this->scenario->iHaveAnImage($details['logo'], 1024);
            $logo = $this->scenario->upload($details['logo']);
            $organization->setLogo(File::fromValues($this->application->storage->add($logo), 'png', 1024));
        }
    }

    private function create(TableNode $table, ?int $ownedBy): void
    {
        $this->application->createOrganization(new CreateOrganizationRequest(...['ownedBy' => $ownedBy, ...$this->requestParameters($table)]));
    }

    /**
     * @return array<string, mixed>
     */
    private function requestParameters(TableNode $table): array
    {
        $parameters = Vocabulary::parameters($table->getRowsHash(), Vocabulary::ORGANIZATION);
        $parameters['logo'] = $this->scenario->upload($parameters['logo']);
        $parameters['holdBy'] = null === $parameters['holdBy'] ? null : $this->scenario->idOf($parameters['holdBy']);

        return $parameters;
    }
}
