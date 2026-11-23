<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHolding;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetails;
use Prospecter\Core\Prospecting\Application\ChangeLogo\ChangeLogo;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\DetachFromHolding\DetachFromHolding;
use Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate;
use Prospecter\Core\Prospecting\Application\RemoveContact\RemoveContact;
use Prospecter\Core\Prospecting\Application\RemoveLogo\RemoveLogo;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentity;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting;
use Prospecter\Core\Prospecting\Application\StopProspecting\StopProspecting;
use Prospecter\Core\Prospecting\Application\WriteObservations\WriteObservations;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\WebPresence;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\StateOf;

final class OrganizationContext implements Context
{
    use SharesTheScenario;

    private ?OrganizationSheet $consulted = null;
    private ?FoundOrganizationPage $found = null;
    private int $max = 20;

    #[Given('l\'organisation :name fait partie de mes organisations')]
    public function anOrganizationOfMine(string $name): Organization
    {
        return $this->anOrganizationProspectedBy($name, $this->myAccountName());
    }

    #[Given('l\'organisation :name fait partie de mes organisations avec :')]
    public function anOrganizationOfMineWith(string $name, TableNode $table): void
    {
        $this->detail($this->anOrganizationOfMine($name), Vocabulary::rows($table));
    }

    #[Given('l\'organisation sans raison sociale :email fait partie de mes organisations')]
    public function anOrganizationOfMineWithoutCorporateName(string $email): void
    {
        $organization = Organization::prospectedBy($this->scenario->myGesture(), new OrganizationId($this->application->newIdentity()), new LegalIdentity(), new ContactDetails(new Email($email)));
        $this->scenario->save($email, $organization);
    }

    #[Given('l\'organisation :name appartient à :owner')]
    public function anOrganizationProspectedBy(string $name, string $owner): Organization
    {
        $organization = Organization::prospectedBy($this->scenario->aGestureIn($this->scenario->accountIdOf($owner)), new OrganizationId($this->application->newIdentity()), new LegalIdentity($name), new ContactDetails());

        return $this->scenario->save($name, $organization);
    }

    #[Given('l\'organisation :name d\'e-mail :email appartient à :owner')]
    public function anOrganizationWithAnEmailProspectedBy(string $name, string $email, string $owner): void
    {
        $organization = Organization::prospectedBy($this->scenario->aGestureIn($this->scenario->accountIdOf($owner)), new OrganizationId($this->application->newIdentity()), new LegalIdentity($name), new ContactDetails(new Email($email)));
        $this->scenario->save($name, $organization);
    }

    /**
     * The address written on one line: its street, then its postal code and its city.
     */
    #[Given('l\'organisation :name au :address appartient à :owner')]
    public function anOrganizationAtAnAddressProspectedBy(string $name, string $address, string $owner): void
    {
        [$street, $postalCodeAndCity] = explode(', ', $address, 2);
        [$postalCode, $city] = explode(' ', $postalCodeAndCity, 2);
        $organization = $this->anOrganizationProspectedBy($name, $owner);
        $organization->relocateTo(new Address($street, $postalCode, $city), null, by: $this->scenario->managerOf($organization), at: $this->application->now());
        $this->application->organizations->save($organization);
    }

    #[Given('l\'organisation :name appartient à :owner avec :')]
    public function anOrganizationProspectedByWith(string $name, string $owner, TableNode $table): void
    {
        $this->detail($this->anOrganizationProspectedBy($name, $owner), Vocabulary::rows($table));
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
        $organization->attachToHolding($this->scenario->organizationNamed($holding), by: $this->scenario->managerOf($organization), at: $this->application->now());
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
        $this->startProspecting($table, byAnAI: false);
    }

    #[Given('l\'IA a commencé à prospecter l\'organisation :')]
    public function theAIStartedProspecting(TableNode $table): void
    {
        $this->startProspecting($table, byAnAI: true);
    }

    private function startProspecting(TableNode $table, bool $byAnAI): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $id = $this->application->newIdentity();
        $this->application->dispatch(new StartProspecting(
            prospectorId: $this->me(),
            id: $id,
            corporateName: $values['raison sociale'],
            form: $values['forme juridique'],
            type: $values['type'],
            email: $values['e-mail'],
            phoneNumber: $values['téléphone'],
            language: $values['langue'],
            street: $values['rue'],
            postalCode: $values['code postal'],
            city: $values['ville'],
            country: $values['pays'],
            observations: $values['observations'],
            holdingId: null === $values['holding'] ? null : $this->scenario->idOf($values['holding']),
            executedAt: $this->application->now(),
            links: Vocabulary::links($values),
            website: $values['site web'],
            byAnAI: $byAnAI,
        ));
        $this->rememberIfAccepted($values['raison sociale'], $id);
    }

    #[When('je révise l\'identité de :name :')]
    public function iReviseTheLegalIdentity(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->application->dispatch(new ReviseLegalIdentity($this->me(), $this->scenario->idOf($name), $values['raison sociale'], $values['forme juridique'], $values['type'], $this->application->now()));
        $this->rememberIfAccepted($values['raison sociale'], $this->scenario->idOf($name));
    }

    #[When('je note le SIRET :siret sur :name')]
    public function iNoteTheSiret(string $siret, string $name): void
    {
        $identity = StateOf::the($this->scenario->organizationNamed($name))->object('legalIdentity', LegalIdentity::class);
        $this->application->dispatch(new ReviseLegalIdentity($this->me(), $this->scenario->idOf($name), $identity->corporateName, $identity->form, $identity->type, $this->application->now(), siret: $siret));
    }

    #[When('je change les coordonnées de :name :')]
    public function iChangeTheContactDetails(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->application->dispatch($this->scenario->isAnOrganization($name)
            ? new ChangeContactDetails($this->me(), $this->scenario->idOf($name), $values['e-mail'], $values['téléphone'], $values['langue'], $this->application->now(), Vocabulary::links($values), $values['site web'])
            : new ChangePersonContactDetails($this->me(), $this->scenario->personIdOf($name), $values['e-mail'], $values['téléphone'], $values['langue'], Vocabulary::links($values), $this->application->now()));
    }

    #[When('je déménage :name à l\'adresse :')]
    public function iRelocate(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->application->dispatch(new Relocate($this->me(), $this->scenario->idOf($name), $values['rue'], $values['code postal'], $values['ville'], $values['pays'], $this->application->now()));
    }

    #[When('j\'efface l\'adresse de :name')]
    public function iEraseTheAddress(string $name): void
    {
        $this->application->dispatch(new Relocate($this->me(), $this->scenario->idOf($name), null, null, null, null, $this->application->now()));
    }

    #[When('j\'écris les observations de :name : :observations')]
    public function iWriteObservations(string $name, string $observations): void
    {
        $this->application->dispatch(new WriteObservations($this->me(), $this->scenario->idOf($name), $observations, $this->application->now()));
    }

    #[When('j\'efface les observations de :name')]
    public function iEraseTheObservations(string $name): void
    {
        $this->application->dispatch(new WriteObservations($this->me(), $this->scenario->idOf($name), null, $this->application->now()));
    }

    #[When('je rattache :name à la holding :holding')]
    public function iAttachToHolding(string $name, string $holding): void
    {
        $this->application->dispatch(new AttachToHolding($this->me(), $this->scenario->idOf($name), $this->scenario->idOf($holding), $this->application->now()));
    }

    #[When('je détache :name de sa holding')]
    public function iDetachFromHolding(string $name): void
    {
        $this->application->dispatch(new DetachFromHolding($this->me(), $this->scenario->idOf($name), $this->application->now()));
    }

    #[When('je change le logo de :name avec :fileName')]
    public function iChangeTheLogo(string $name, string $fileName): void
    {
        $this->application->dispatch(new ChangeLogo($this->me(), $this->scenario->idOf($name), $this->scenario->upload($fileName), $this->application->now()));
    }

    #[When('je retire le logo de :name')]
    public function iRemoveTheLogo(string $name): void
    {
        $this->application->dispatch(new RemoveLogo($this->me(), $this->scenario->idOf($name), $this->application->now()));
    }

    #[Given(':contact est un contact de :organization')]
    public function aContactOfTheOrganization(string $contact, string $organization): void
    {
        $prospect = $this->scenario->organizationNamed($organization);
        $prospect->addContact($this->scenario->contactIdOf($contact), by: $this->scenario->managerOf($prospect), at: $this->application->now());
        $this->application->organizations->save($prospect);
    }

    #[When('je rattache le contact :contact à :organization')]
    public function iAddAContact(string $contact, string $organization): void
    {
        $this->application->dispatch(new AddContact($this->me(), $this->scenario->idOf($organization), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('je retire le contact :contact de :organization')]
    public function iRemoveAContact(string $contact, string $organization): void
    {
        $this->application->dispatch(new RemoveContact($this->me(), $this->scenario->idOf($organization), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('j\'arrête de prospecter :name')]
    public function iStopProspecting(string $name): void
    {
        $this->application->dispatch(new StopProspecting($this->me(), $this->scenario->idOf($name), $this->application->now()));
    }

    #[Then('les contacts de :organization sont :')]
    public function theContactsOfTheOrganizationAre(string $organization, TableNode $table): void
    {
        Expect::same($table->getColumn(0), array_map($this->scenario->nameOfPerson(...), StateOf::the($this->scenario->organizationNamed($organization))->listOf('contacts', ContactId::class)));
    }

    #[Then(':organization n\'a pas de contact')]
    public function theOrganizationHasNoContact(string $organization): void
    {
        Expect::same([], StateOf::the($this->scenario->organizationNamed($organization))->listOf('contacts', ContactId::class));
    }

    #[When('je consulte l\'organisation :name')]
    public function iConsultTheOrganization(string $name): void
    {
        $this->consulted = $this->application->consultOrganization(new ConsultOrganization($this->scenario->idOf($name)));
    }

    #[When('je liste mes organisations')]
    public function iListMyOrganizations(): void
    {
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizations($this->me()));
    }

    #[When('je cherche :query dans mes organisations')]
    public function iSearchMyOrganizations(string $query): void
    {
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizations($this->me(), query: $query));
    }

    #[When('je liste mes organisations, :max à la fois')]
    public function iListMyOrganizationsBySlices(int $max): void
    {
        $this->max = $max;
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizations($this->me(), max: $max));
    }

    #[When('je demande la suite')]
    public function iAskForTheNextSlice(): void
    {
        $next = Expect::present($this->found?->next, 'A next slice was expected');
        $this->found = $this->application->searchProspectedOrganizations(new SearchProspectedOrganizations($this->me(), after: $next, max: $this->max));
    }

    #[Then('l\'organisation :name est enregistrée avec :')]
    public function theOrganizationIsStoredWith(string $name, TableNode $table): void
    {
        Expect::describes(Vocabulary::rows($table), Vocabulary::describeOrganization($this->scenario->organizationNamed($name), $this->scenario->nameOf(...), $this->scenario->me()->id()));
    }

    #[Then('l\'organisation :name compte :count organisation(s) active(s)')]
    public function theOrganizationCountsActiveOrganizations(string $name, int $count): void
    {
        Expect::same($count, count($this->application->foundOrganizationRetriever->sliceProspectedBy($this->scenario->accountIdOf($name), null, null, PHP_INT_MAX)->organizations));
    }

    #[Then('je vois l\'organisation :')]
    public function iSeeTheOrganization(TableNode $table): void
    {
        $consulted = Expect::present($this->consulted, sprintf('The organization was expected, got: %s', $this->application->refusal()?->getMessage()));
        $organization = $consulted;

        Expect::describes(Vocabulary::rows($table), [
            'raison sociale' => (string) $organization->corporateName,
            'forme juridique' => (string) $organization->form,
            'SIRET' => (string) $organization->siret,
            'liens' => implode(' ', array_column($organization->links, 'address')),
            'site web' => (string) $organization->website,
            'e-mail' => (string) $organization->email,
            'ville' => (string) $organization->city,
            'position' => Vocabulary::describePosition($organization->longitude, $organization->latitude),
            'logo' => null === $organization->logo ? 'aucun' : basename($organization->logo->url),
            'badge' => Vocabulary::describeBadge($organization->createdByAnAI),
        ]);
    }

    #[Then('je peux :')]
    public function iMay(TableNode $table): void
    {
        $consulted = Expect::present($this->consulted, sprintf('The organization was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($consulted->possibleIntents));
    }

    #[Given(':name a été signalée comme doublon possible de :other')]
    public function theOrganizationWasFlaggedAsAPossibleDuplicateOf(string $name, string $other): void
    {
        $this->suspectDuplicate($this->scenario->organizationNamed($name), $this->scenario->organizationNamed($other));
        $this->suspectDuplicate($this->scenario->organizationNamed($other), $this->scenario->organizationNamed($name));
    }

    private function suspectDuplicate(Organization $organization, Organization $duplicate): void
    {
        $organization->suspectDuplicateOf($duplicate->id(), by: $this->scenario->managerOf($organization), at: $this->application->now());
        $this->application->organizations->save($organization);
    }

    #[Given('j\'ai dit que :name n\'est pas un doublon de :other')]
    #[When('je dis que :name n\'est pas un doublon de :other')]
    public function iDismissThePossibleDuplicate(string $name, string $other): void
    {
        $this->application->dispatch(new DismissPossibleDuplicate($this->me(), $this->scenario->idOf($name), $this->scenario->idOf($other), $this->application->now()));
    }

    #[Then(':name est signalée comme doublon possible de :other')]
    public function theOrganizationIsFlaggedAsAPossibleDuplicateOf(string $name, string $other): void
    {
        Expect::same([$other], $this->possibleDuplicatesOf($name));
    }

    /**
     * An organization stopped meanwhile can lift a suspicion in return. The reaction then has nothing left to do,
     * and it fails no event.
     */
    #[Then('aucune levée de soupçon n\'a échoué')]
    public function noLiftingOfASuspicionFailed(): void
    {
        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(DuplicateSuspicionLifted::class)));
    }

    #[Then(':name n\'est pas signalée')]
    public function theOrganizationIsNotFlagged(string $name): void
    {
        Expect::same([], $this->possibleDuplicatesOf($name));
    }

    /**
     * @return list<string> the full names of the organizations it may be a duplicate of
     */
    private function possibleDuplicatesOf(string $name): array
    {
        $sheet = $this->application->consultOrganization(new ConsultOrganization($this->scenario->idOf($name)));
        $sheet = Expect::present($sheet, sprintf('The organization was expected, got: %s', $this->application->refusal()?->getMessage()));

        return array_map(static fn (PossibleDuplicate $duplicate): string => $duplicate->fullName, $sheet->possibleDuplicates);
    }

    #[Then('je trouve :count organisation(s)')]
    public function iFindOrganizations(int $count): void
    {
        $found = Expect::present($this->found, sprintf('Organizations were expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($count, count($found->organizations));
    }

    #[Then('il y a une suite')]
    public function thereIsANextSlice(): void
    {
        Expect::that(null !== $this->found?->next, 'A next slice was expected');
    }

    #[Then('il n\'y a pas de suite')]
    public function thereIsNoNextSlice(): void
    {
        Expect::that(null !== $this->found && null === $this->found->next, 'No next slice was expected');
    }

    #[Then('la liste affiche :')]
    public function theListShows(TableNode $table): void
    {
        $found = Expect::present($this->found, 'Organizations were expected');
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $shown = array_map(static fn (FoundOrganization $organization): array => array_intersect_key([
            'nom' => $organization->fullName,
            'ville' => (string) $organization->city,
            'doublon possible de' => implode(', ', array_map(static fn (PossibleDuplicate $duplicate): string => $duplicate->fullName, $organization->possibleDuplicates)),
        ], $columns), $found->organizations);

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

    private function me(): string
    {
        return $this->application->signedIn();
    }

    private function myAccountName(): string
    {
        return $this->scenario->nameOf($this->scenario->me()->accountId());
    }

    private function rememberIfAccepted(?string $name, string $id): void
    {
        if (null === $this->application->refusal()) {
            $this->scenario->remember($name, $id);
        }
    }

    /**
     * @param array<string, string> $details
     */
    private function detail(Organization $organization, array $details): void
    {
        $values = Vocabulary::values(array_diff_key($details, ['logo' => true]));
        $manager = $this->scenario->managerOf($organization);
        $organization->reviseLegalIdentity(new LegalIdentity(StateOf::the($organization)->object('legalIdentity', LegalIdentity::class)->corporateName, $values['forme juridique'], siret: Siret::orNone($values['SIRET'])), by: $manager, at: $this->application->now());
        $organization->changeContactDetails(Vocabulary::contactDetails($values), new WebPresence(ExternalReferences::designating(Vocabulary::links($values), Designated::Organization), Website::orNone($values['site web'])), $manager, $this->application->now());
        $address = Vocabulary::address($values);
        $organization->relocateTo($address, null === $address ? null : $this->application->geoLocation->find((string) $address), by: $manager, at: $this->application->now());

        if (isset($details['logo'])) {
            $organization->changeLogo($this->storedImage($details['logo']), by: $manager, at: $this->application->now());
        }

        $this->application->organizations->save($organization);
    }

    private function storedImage(string $fileName): File
    {
        $this->scenario->iHaveAnImage($fileName, 1024);

        return new File($this->application->storage->add($this->scenario->upload($fileName)), 'png', 1024);
    }
}
