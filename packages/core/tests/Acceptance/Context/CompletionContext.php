<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\CompareWithContact\CompareWithContact;
use Prospecter\Core\PersonalData\Application\CompleteContact\CompleteContact;
use Prospecter\Core\SharedKernel\Application\Comparison;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * What a client knows of a contact or an organization Prospecter knows already: what is empty is completed, what is
 * noted stays (ADR 0177).
 */
final class CompletionContext implements Context
{
    use SharesTheScenario;

    /** The fields of a contact or an organization, as a table names them. */
    private const array FIELDS = [
        'civility' => 'Civilité',
        'jobTitle' => 'fonction',
        'email' => 'e-mail',
        'phoneNumber' => 'téléphone',
        'links' => 'liens',
    ];

    private ?Comparison $comparison = null;

    #[When('je complète le contact :name :')]
    public function iCompleteTheContact(string $name, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $this->application->dispatch(new CompleteContact($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], Vocabulary::links($values), $this->application->now()));
    }

    #[When('je compare le contact :name à :')]
    public function iCompareTheContact(string $name, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $this->comparison = $this->application->compareWithContact(new CompareWithContact($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], Vocabulary::links($values)));
    }

    #[Then('serait complété : :fields')]
    public function wouldBeCompleted(string $fields): void
    {
        Expect::that(null !== $this->comparison, sprintf('The comparison was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($fields, self::described($this->comparison->completed));
    }

    #[Then('diffère : :fields')]
    public function differs(string $fields): void
    {
        Expect::that(null !== $this->comparison, 'The comparison was expected');
        Expect::same($fields, self::described($this->comparison->differing));
    }

    /**
     * @param list<string> $fields
     */
    private static function described(array $fields): string
    {
        return implode(', ', array_map(static fn (string $field): string => self::FIELDS[$field], $fields));
    }
}
