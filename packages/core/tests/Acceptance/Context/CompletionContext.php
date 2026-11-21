<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\CompleteContact\CompleteContact;
use Tests\Core\Acceptance\Vocabulary;

/**
 * What a client knows of a contact or an organization Prospecter knows already: what is empty is completed, what is
 * noted stays (ADR 0177).
 */
final class CompletionContext implements Context
{
    use SharesTheScenario;

    #[When('je complète le contact :name :')]
    public function iCompleteTheContact(string $name, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $this->application->dispatch(new CompleteContact($this->application->signedIn(), $this->scenario->personIdOf($name), Vocabulary::civility($values['Civilité']), $values['fonction'], $values['e-mail'], $values['téléphone'], Vocabulary::links($values), $this->application->now()));
    }
}
