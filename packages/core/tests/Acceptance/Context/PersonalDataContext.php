<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Application\ChangeJobTitle\ChangeJobTitle;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContact;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ForgetContact\ForgetContact;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContactsResponse;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
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

    private ?PersonResponse $consulted = null;
    private ?SearchContactsResponse $found = null;
    /** @var array<string, PersonResponse>|null */
    private ?array $batch = null;

    #[Given(':name est membre de :account avec le profil :')]
    public function aColleague(string $name, string $account, TableNode $table): void
    {
        $member = $this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector);
        $values = Vocabulary::values($table->getRowsHash());
        $person = $this->application->persons->get($member->personId());
        $theColleague = $this->application->readerService->readerFrom($member->id());
        $person->rename(Vocabulary::personName($values), by: $theColleague, at: $this->application->now());
        $person->changeContactDetails(Vocabulary::contactDetails($values), by: $theColleague, at: $this->application->now());
        $this->application->persons->save($person);
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

    private function aContactWith(string $name, string $organization, ContactDetails $contactDetails): void
    {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $prospect = $this->scenario->organizationNamed($organization);
        $contact = Person::collectAsContact(
            $this->scenario->managerOfPersonalDataOf($prospect->accountId()),
            new PersonId($this->application->newIdentity()),
            new PersonName($firstName, $lastName),
            null,
            $contactDetails,
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

    #[When('je change la fonction de :name : :jobTitle')]
    public function iChangeTheJobTitle(string $name, string $jobTitle): void
    {
        $this->application->dispatch(new ChangeJobTitle($this->application->signedIn(), $this->scenario->personIdOf($name), $jobTitle, $this->application->now()));
    }

    #[When('j\'efface la fiche de :name')]
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

    #[Then('je vois la fiche :')]
    public function iSeeThePerson(TableNode $table): void
    {
        Expect::that(null !== $this->consulted, sprintf('The person was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::describes($table->getRowsHash(), $this->describe($this->consulted));
    }

    #[Then('je vois les fiches :')]
    public function iSeeThePersons(TableNode $table): void
    {
        Expect::that(null !== $this->batch, sprintf('The persons were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $seen = array_map(fn (PersonResponse $person): array => array_intersect_key($this->describe($person), $columns), array_values($this->batch));
        usort($seen, static fn (array $a, array $b): int => strcmp((string) reset($a), (string) reset($b)));

        Expect::same($table->getColumnsHash(), $seen);
    }

    #[Then('la liste des contacts affiche :')]
    public function theListOfContactsShows(TableNode $table): void
    {
        Expect::that(null !== $this->found, sprintf('Contacts were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip(array_keys($table->getColumnsHash()[0] ?? []));
        $shown = array_map(fn (PersonResponse $contact): array => array_intersect_key($this->describe($contact), $columns), $this->found->contacts);

        Expect::same($table->getColumnsHash(), $shown);
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

    /**
     * @param list<string> $organizationIds
     */
    private function collect(string $name, array $organizationIds, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $id = $this->application->newIdentity();
        $this->application->dispatch(new CollectContact($this->application->signedIn(), $id, $values['prénom'], $values['nom'], $values['fonction'], $values['e-mail'], $values['téléphone'], $values['langue'], $organizationIds, $this->application->now()));

        if (null === $this->application->refusal()) {
            $this->scenario->rememberPerson($name, new PersonId($id));
        }
    }

    /**
     * @return array<string, string>
     */
    private function describe(PersonResponse $person): array
    {
        return [
            'prénom' => (string) $person->firstName,
            'nom' => (string) $person->lastName,
            'fonction' => (string) $person->jobTitle,
            'e-mail' => (string) $person->email,
            'téléphone' => (string) $person->phoneNumber,
            'photo' => null === $person->picture ? 'aucune' : basename($person->picture->url),
            'masquée' => $person->masked ? 'oui' : 'non',
            'modifiée le' => Vocabulary::describeMoment($person->modifiedAt),
        ];
    }
}
