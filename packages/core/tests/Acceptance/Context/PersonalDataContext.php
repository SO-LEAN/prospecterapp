<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
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
    /** @var array<string, PersonResponse>|null */
    private ?array $batch = null;

    #[Given(':name est membre de :account avec le profil :')]
    public function aColleague(string $name, string $account, TableNode $table): void
    {
        $member = $this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector);
        $values = Vocabulary::values($table->getRowsHash());
        $person = $this->application->persons->get($member->personId());
        $theColleague = $this->application->readers->get($member->id());
        $person->rename(Vocabulary::personName($values), by: $theColleague, at: $this->application->now());
        $person->changeContactDetails(Vocabulary::contactDetails($values), by: $theColleague, at: $this->application->now());
        $this->application->persons->save($person);
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

    #[Then('je peux sur sa fiche :')]
    public function iMayOnThePerson(TableNode $table): void
    {
        Expect::that(null !== $this->consulted, 'The person was expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->consulted->possibleIntents));
    }

    /**
     * @return array<string, string>
     */
    private function describe(PersonResponse $person): array
    {
        return [
            'prénom' => (string) $person->firstName,
            'nom' => (string) $person->lastName,
            'e-mail' => (string) $person->email,
            'téléphone' => (string) $person->phoneNumber,
            'photo' => null === $person->picture ? 'aucune' : basename($person->picture->url),
            'masquée' => $person->masked ? 'oui' : 'non',
            'modifiée le' => Vocabulary::describeMoment($person->modifiedAt),
        ];
    }
}
