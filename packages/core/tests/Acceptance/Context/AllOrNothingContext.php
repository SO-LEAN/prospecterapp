<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Application\OpenAccount\OpenAccount;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\FailingReaction;
use Tests\Core\Acceptance\Vocabulary;

final class AllOrNothingContext implements Context
{
    use SharesTheScenario;

    #[Given('une réaction à l\'ouverture d\'un compte qui refuse : :message')]
    public function aReactionToTheOpeningOfAnAccountRefuses(string $message): void
    {
        $this->application->alsoReactWith(new FailingReaction(BusinessRuleViolation::because($message)));
    }

    #[Given('une réaction à l\'ouverture d\'un compte qui échoue')]
    public function aReactionToTheOpeningOfAnAccountFails(): void
    {
        $this->application->alsoReactWith(new FailingReaction(new \RuntimeException('The reaction failed')));
    }

    #[When('j\'ouvre le compte de mon organisation, en vain :')]
    public function iOpenTheAccountOfMyOrganizationInVain(TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());

        try {
            $this->application->dispatch(new OpenAccount($this->application->newIdentity(), $values['raison sociale'], $values['forme juridique'], $this->application->newIdentity(), $this->application->newIdentity(), $this->application->now()));
        } catch (\RuntimeException) {
            return;
        }

        throw new \LogicException('The opening of the account did not fail');
    }

    #[Then('rien n\'est écrit')]
    public function nothingIsWritten(): void
    {
        Expect::that($this->application->database->isEmpty(), 'Something is written');
    }
}
