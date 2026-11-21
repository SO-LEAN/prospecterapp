<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\PersonalData\Application\ChangeCivility\ChangeCivility;
use Prospecter\Core\PersonalData\Application\ChangeJobTitle\ChangeJobTitle;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContact;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Prospecter\Core\PersonalData\Application\ForgetContact\ForgetContact;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The persons of the scenarios read and changed through PersonalData: who sees them in clear, masked, or not
 * at all.
 */
final class PersonalDataContext implements Context
{
    use SharesTheScenario;

    private ?PersonCard $consulted = null;
    private ?ContactPage $found = null;
    private ?int $foundInAll = null;
    private ?SearchContactsByTags $searchedByTags = null;
    /** @var array<string, PersonCard>|null */
    private ?array $batch = null;

    #[Given(':name est membre de :account avec le profil :')]
    public function aColleague(string $name, string $account, TableNode $table): void
    {
        $member = $this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector);
        $values = Vocabulary::values($table->getRowsHash());
        $person = $this->application->persons->get($this->scenario->profileOf($member));
        $theColleague = $this->application->readers->readerFrom($member->id());
        $person->rename(Vocabulary::personName($values), by: $theColleague, at: $this->application->now());
        $person->changeContactDetails(Vocabulary::contactDetails($values), Vocabulary::personReferences($values), by: $theColleague, at: $this->application->now());
        $this->application->persons->save($person);
    }

    #[Given('le contact :name de :organization')]
    public function aContactWithoutContactDetails(string $name, string $organization): void
    {
        $this->aContactWith($name, $organization, new ContactDetails());
    }

    #[Given('le contact :name de :organization est aussi contact de :other')]
    public function aContactOfTwoOrganizations(string $name, string $organization, string $other): void
    {
        $this->aContactWithoutContactDetails($name, $organization);
        $prospect = $this->scenario->organizationNamed($other);
        $prospect->addContact($this->scenario->personNamed($name)->id(), by: $this->scenario->managerOf($prospect), at: $this->application->now());
        $this->application->organizations->save($prospect);
    }

    #[Given('le contact :name de :organization a l\'adresse e-mail :email')]
    public function aContactOf(string $name, string $organization, string $email): void
    {
        $this->aContactWith($name, $organization, new ContactDetails(new Email($email)));
    }

    #[Given('le contact :name de :organization a le téléphone :phoneNumber')]
    public function aContactWithThePhoneNumber(string $name, string $organization, string $phoneNumber): void
    {
        $this->aContactWith($name, $organization, new ContactDetails(phoneNumber: PhoneNumber::of($phoneNumber)));
    }

    #[Given('la fonction de :name est :jobTitle')]
    public function theJobTitleIs(string $name, string $jobTitle): void
    {
        $person = $this->scenario->personNamed($name);
        $person->changeJobTitle($jobTitle, by: $this->scenario->managerOfPersonalDataOf($person->accountId()), at: $this->application->now());
        $this->application->persons->save($person);
    }

    /**
     * Noted as it is, whether another contact noted it too or not: as before the references were unique.
     */
    #[Given(':name a noté le lien :address')]
    public function aPersonWithTheLink(string $name, string $address): void
    {
        $person = $this->scenario->personNamed($name);
        $links = ExternalReferences::of([...$person->references()->all(), ExternalReference::designating($address, Designated::Person)]);
        $person->changeContactDetails($person->contactDetails(), $links, by: $this->scenario->managerOfPersonalDataOf($person->accountId()), at: $this->application->now());
        $this->application->persons->save($person);
    }

    #[Given(':name a la civilité :civility')]
    public function aPersonWithTheCivility(string $name, string $civility): void
    {
        $person = $this->scenario->personNamed($name);
        $person->changeCivility(Civility::from(Vocabulary::civility($civility)), by: $this->scenario->managerOfPersonalDataOf($person->accountId()), at: $this->application->now());
        $this->application->persons->save($person);
    }

    private function aContactWith(string $name, string $organization, ContactDetails $contactDetails): void
    {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $prospect = $this->scenario->organizationNamed($organization);
        $contact = Person::collectAsContact(
            $this->scenario->managerOfPersonalDataOf($prospect->accountId()),
            new PersonId($this->application->newIdentity()),
            new PersonName($firstName, $lastName),
            Civility::X,
            null,
            $contactDetails,
            ExternalReferences::none(),
            [$prospect->id()],
            $this->application->now(),
        );
        $this->application->persons->save($contact);
        $prospect->addContact($contact->id(), by: $this->scenario->managerOf($prospect), at: $this->application->now());
        $this->application->organizations->save($prospect);
        $this->scenario->rememberPerson($name, $contact->id());
    }

    #[When('j\'enregistre le contact :name pour :organization :')]
    public function iCollectAContactFor(string $name, string $organization, TableNode $table): void
    {
        $this->collect($name, [$this->scenario->idOf($organization)], $table);
    }

    #[When('j\'enregistre le contact :name sans organisation :')]
    public function iCollectAContactAlone(string $name, TableNode $table): void
    {
        $this->collect($name, [], $table);
    }

    /**
     * As the worker may deliver it once more, or after a client attached the contact meanwhile (ADR 0177).
     */
    #[When('la collecte de :name est délivrée une fois de plus')]
    public function theCollectionIsDeliveredAgain(string $name): void
    {
        $personId = $this->scenario->personIdOf($name);
        $collected = array_filter($this->application->events->published(ContactCollected::class), static fn (ContactCollected $event): bool => $event->personId === $personId);
        $this->application->publishAgain(array_last($collected));
    }

    #[Then('aucun rattachement de contact collecté n\'a échoué')]
    public function noAttachmentOfACollectedContactFailed(): void
    {
        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(ContactCollected::class)));
    }

    #[When('je liste mes contacts')]
    public function iListMyContacts(): void
    {
        $this->found = $this->application->searchContacts(new SearchContacts($this->application->signedIn()));
    }

    #[When('je cherche :text dans mes contacts')]
    public function iSearchMyContacts(string $text): void
    {
        $this->found = $this->application->searchContacts(new SearchContacts($this->application->signedIn(), query: $text));
    }

    /**
     * As the API composes them: Campaigns finds the contacts carrying the tags, PersonalData reads them.
     */
    #[When('je cherche mes contacts tagués :tags')]
    public function iSearchMyContactsTagged(string $tags): void
    {
        $this->searchByTags(new SearchContactsByTags($this->application->signedIn(), explode(' ', $tags)));
    }

    #[When('je cherche mes contacts tagués :tags par tranches de :max')]
    public function iSearchMyContactsTaggedBySlicesOf(string $tags, int $max): void
    {
        $this->searchByTags(new SearchContactsByTags($this->application->signedIn(), explode(' ', $tags), max: $max));
    }

    #[When('je demande la tranche suivante')]
    public function iAskTheNextSlice(): void
    {
        Expect::that(null !== $this->searchedByTags && null !== $this->found?->next, 'A next slice was expected');
        $this->searchByTags(new SearchContactsByTags($this->searchedByTags->memberId, $this->searchedByTags->tags, $this->found->next, $this->searchedByTags->max));
    }

    #[Then('c\'est la dernière tranche')]
    public function itIsTheLastSlice(): void
    {
        Expect::that(null !== $this->found && null === $this->found->next, 'The last slice was expected');
    }

    private function searchByTags(SearchContactsByTags $query): void
    {
        $this->searchedByTags = $query;
        $page = $this->application->searchContactsByTags($query);
        $this->found = null;
        $this->foundInAll = $page?->found;

        if (null !== $page) {
            $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), $page->contactIds)) ?? [];
            $this->found = new ContactPage(array_values(array_filter(array_map(static fn (string $id): ?PersonCard => $read[$id] ?? null, $page->contactIds))), $page->next, []);
        }
    }

    #[When('je change la fonction de :name : :jobTitle')]
    public function iChangeTheJobTitle(string $name, string $jobTitle): void
    {
        $this->application->dispatch(new ChangeJobTitle($this->application->signedIn(), $this->scenario->personIdOf($name), $jobTitle, $this->application->now()));
    }

    #[When('je change la civilité de :name en :civility')]
    public function iChangeTheCivility(string $name, string $civility): void
    {
        $this->application->dispatch(new ChangeCivility($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($civility), $this->application->now()));
    }

    #[When('je retire la civilité de :name')]
    public function iRemoveTheCivility(string $name): void
    {
        $this->application->dispatch(new ChangeCivility($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility('inconnue'), $this->application->now()));
    }

    #[When('j\'efface les contacts :one et :other')]
    public function iForgetBoth(string $one, string $other): void
    {
        $this->iForget($one);
        $this->iForget($other);
    }

    #[When('j\'efface la fiche de :name')]
    #[When('j\'efface le contact :name')]
    public function iForget(string $name): void
    {
        $this->application->dispatch(new ForgetContact($this->application->signedIn(), $this->scenario->personIdOf($name), $this->application->now()));
    }

    #[When('je consulte la fiche de :name')]
    public function iConsultThePersonOf(string $name): void
    {
        $this->consulted = $this->application->consultPerson(new ConsultPerson($this->application->signedIn(), $this->scenario->personIdOf($name)));
    }

    #[When('/^je consulte ensemble les fiches de (?<names>.+)$/')]
    public function iConsultThePersonsOf(string $names): void
    {
        $ids = array_map($this->scenario->personIdOf(...), (array) preg_split('/, | et /', $names));
        $this->batch = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), $ids));
    }

    #[When('je change le nom de :name :')]
    public function iRename(string $name, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $this->application->dispatch(new RenamePerson($this->application->signedIn(), $this->scenario->personIdOf($name), $values['prénom'], $values['nom'], $this->application->now()));
    }

    /**
     * Both ways, as the reaction leaves them.
     */
    #[Given('le contact :name a été signalé comme doublon possible de :other')]
    public function theContactWasSuspected(string $name, string $other): void
    {
        $contact = $this->scenario->personNamed($name);
        $duplicate = $this->scenario->personNamed($other);
        $by = $this->scenario->managerOfPersonalDataOf($contact->accountId())->id;
        $contact->suspectDuplicateOf($duplicate->id(), by: $by, at: $this->application->now());
        $duplicate->suspectDuplicateOf($contact->id(), by: $by, at: $this->application->now());
        $this->application->persons->save($contact);
        $this->application->persons->save($duplicate);
    }

    #[Given('j\'ai dit que le contact :name n\'est pas un doublon de :other')]
    #[When('je dis que le contact :name n\'est pas un doublon de :other')]
    public function iDismissTheSuspicion(string $name, string $other): void
    {
        $this->application->dispatch(new DismissPossibleDuplicate($this->application->signedIn(), $this->scenario->personIdOf($name), $this->scenario->personIdOf($other), $this->application->now()));
    }

    #[Then('aucune levée de soupçon de contact n\'a échoué')]
    public function noLiftingOfASuspicionOfContactFailed(): void
    {
        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(DuplicateSuspicionLifted::class)));
    }

    #[Then('le contact :name est signalé comme doublon possible de :other')]
    public function theContactIsSuspected(string $name, string $other): void
    {
        Expect::same([$other], $this->possibleDuplicatesOf($name));
    }

    #[Then('le contact :name n\'est pas signalé')]
    public function theContactIsNotSuspected(string $name): void
    {
        Expect::same([], $this->possibleDuplicatesOf($name));
    }

    /**
     * Read on their card, by the member signed in.
     *
     * @return list<string>
     */
    private function possibleDuplicatesOf(string $name): array
    {
        $card = $this->application->consultPerson(new ConsultPerson($this->application->signedIn(), $this->scenario->personIdOf($name)));
        Expect::that(null !== $card, sprintf('The card of %s was expected', $name));

        return array_map(static fn (PossibleDuplicate $duplicate): string => trim(sprintf('%s %s', $duplicate->firstName, $duplicate->lastName)), $card->possibleDuplicates);
    }

    #[Then('je vois la fiche :')]
    public function iSeeThePerson(TableNode $table): void
    {
        Expect::that(null !== $this->consulted, sprintf('The person was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::describes($table->getRowsHash(), $this->describe($this->consulted));
    }

    #[Then('je vois la fiche de :name avec :')]
    public function iSeeThePersonOf(string $name, TableNode $table): void
    {
        $this->iConsultThePersonOf($name);
        $this->iSeeThePerson($table);
    }

    #[Then('ses liens sont masqués')]
    public function theLinksAreMasked(): void
    {
        Expect::that(null !== $this->consulted, 'The person was expected');
        Expect::that($this->consulted->masked, 'The person was expected masked');
        Expect::same('***', Vocabulary::describeLinks($this->consulted->links));
    }

    #[Then('sa civilité n\'est pas renseignée')]
    public function theCivilityIsNotNoted(): void
    {
        Expect::that(null !== $this->consulted, sprintf('The person was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same(Vocabulary::civility('inconnue'), $this->consulted->civility);
    }

    #[Then('je vois les fiches :')]
    public function iSeeThePersons(TableNode $table): void
    {
        Expect::that(null !== $this->batch, sprintf('The persons were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $seen = array_map(fn (PersonCard $person): array => array_intersect_key($this->describe($person), $columns), array_values($this->batch));
        usort($seen, static fn (array $a, array $b): int => strcmp((string) reset($a), (string) reset($b)));

        Expect::same($table->getColumnsHash(), $seen);
    }

    #[Then('la liste des contacts affiche :')]
    public function theListOfContactsShows(TableNode $table): void
    {
        Expect::that(null !== $this->found, sprintf('Contacts were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $shown = array_map(fn (PersonCard $contact): array => array_intersect_key($this->describe($contact), $columns), $this->found->contacts);

        Expect::same($table->getColumnsHash(), $shown);
    }

    #[Then('la liste des contacts est vide')]
    public function theListOfContactsIsEmpty(): void
    {
        Expect::that(null !== $this->found, sprintf('Contacts were expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same([], $this->found->contacts);
    }

    #[Then(':name n\'est pas dans la liste')]
    public function isNotInTheList(string $name): void
    {
        Expect::that(null !== $this->found, sprintf('Contacts were expected, got: %s', $this->application->refusal()?->getMessage()));
        $absent = $this->scenario->personIdOf($name);
        Expect::that(!array_any($this->found->contacts, static fn (PersonCard $contact): bool => $contact->id === $absent), sprintf('%s is in the list', $name));
    }

    #[Then(':count contacts sont trouvés')]
    public function contactsAreFound(int $count): void
    {
        Expect::same($count, $this->foundInAll);
    }

    #[Then('je peux sur mes contacts :')]
    public function iMayOnMyContacts(TableNode $table): void
    {
        Expect::that(null !== $this->found, 'Contacts were expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->found->possibleIntents));
    }

    #[Then('je ne peux rien faire sur mes contacts')]
    public function iMayDoNothingOnMyContacts(): void
    {
        Expect::that(null !== $this->found, 'Contacts were expected');
        Expect::same([], $this->found->possibleIntents);
    }

    #[Then('je peux sur sa fiche :')]
    public function iMayOnThePerson(TableNode $table): void
    {
        Expect::that(null !== $this->consulted, 'The person was expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->consulted->possibleIntents));
    }

    #[Then('je ne peux pas sur sa fiche :intent')]
    public function iMayNotOnThePerson(string $intent): void
    {
        Expect::that(null !== $this->consulted, 'The person was expected');
        Expect::that(!in_array($intent, Vocabulary::describeIntents($this->consulted->possibleIntents), true), sprintf('"%s" was not expected', $intent));
    }

    /**
     * @param list<string> $organizationIds
     */
    private function collect(string $name, array $organizationIds, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $id = $this->application->newIdentity();
        $this->application->dispatch(new CollectContact($this->application->signedIn(), $id, $values['prénom'], $values['nom'], Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], $values['langue'], Vocabulary::links($values), $organizationIds, $this->application->now()));

        if (null === $this->application->refusal()) {
            $this->scenario->rememberPerson($name, new PersonId($id));
        }
    }

    /**
     * @return array<string, string>
     */
    private function describe(PersonCard $person): array
    {
        return [
            'Civilité' => Vocabulary::describeCivility($person->civility),
            'prénom' => (string) $person->firstName,
            'nom' => (string) $person->lastName,
            'fonction' => (string) $person->jobTitle,
            'e-mail' => (string) $person->email,
            'téléphone' => (string) $person->phoneNumber,
            'liens' => Vocabulary::describeLinks($person->links),
            'photo' => null === $person->picture ? 'aucune' : basename($person->picture->url),
            'masquée' => Vocabulary::describeYesOrNo($person->masked),
            'modifiée le' => Vocabulary::describeMoment($person->modifiedAt),
        ];
    }
}
