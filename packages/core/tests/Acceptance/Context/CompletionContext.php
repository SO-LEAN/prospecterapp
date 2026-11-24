<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\Contact\CompareWithContact\CompareWithContact;
use Prospecter\Core\PersonalData\Application\Contact\CompleteContact\CompleteContact;
use Prospecter\Core\Prospecting\Application\CompareWithOrganization\CompareWithOrganization;
use Prospecter\Core\Prospecting\Application\CompleteOrganization\CompleteOrganization;
use Prospecter\Core\SharedKernel\Application\Response\Comparison;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * What a client knows about a contact or an organization that Prospecter already knows. The empty fields get
 * filled. The recorded values stay (ADR 0177).
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
        'siret' => 'SIRET',
        'website' => 'site web',
    ];

    private ?Comparison $comparison = null;

    #[When('je complète le contact :name :')]
    #[When('l\'IA complète le contact :name :')]
    public function iCompleteTheContact(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->application->dispatch(new CompleteContact($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], Vocabulary::links($values), $this->application->now()));
    }

    #[When('je complète l\'organisation :name :')]
    public function iCompleteTheOrganization(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->application->dispatch(new CompleteOrganization($this->application->signedIn(), $this->scenario->idOf($name), $values['SIRET'], Vocabulary::links($values), $values['site web'], $values['e-mail'], $values['téléphone'], $this->application->now()));
    }

    #[When('je compare le contact :name à :')]
    public function iCompareTheContact(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->comparison = $this->application->compareWithContact(new CompareWithContact($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], Vocabulary::links($values)));
    }

    #[When('je compare l\'organisation :name à :')]
    public function iCompareTheOrganization(string $name, TableNode $table): void
    {
        $values = Vocabulary::values(Vocabulary::rows($table));
        $this->comparison = $this->application->compareWithOrganization(new CompareWithOrganization($this->scenario->idOf($name), $values['SIRET'], Vocabulary::links($values), $values['site web'], $values['e-mail'], $values['téléphone']));
    }

    #[Then('serait complété : :fields')]
    public function wouldBeCompleted(string $fields): void
    {
        $comparison = Expect::present($this->comparison, sprintf('The comparison was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($fields, self::described($comparison->completed));
    }

    #[Then('diffère : :fields')]
    public function differs(string $fields): void
    {
        $comparison = Expect::present($this->comparison, 'The comparison was expected');
        Expect::same($fields, self::described($comparison->differing));
    }

    /**
     * @param list<string> $fields
     */
    private static function described(array $fields): string
    {
        return implode(', ', array_map(static fn (string $field): string => self::FIELDS[$field], $fields));
    }
}
