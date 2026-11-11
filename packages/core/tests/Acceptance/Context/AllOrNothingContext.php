<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\FailingReaction;

final class AllOrNothingContext implements Context
{
    use SharesTheScenario;

    private ?OrganizationId $prospectedByTheReaction = null;

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

    #[Given('une réaction à l\'ouverture d\'un compte qui prospecte :name puis échoue')]
    public function aReactionToTheOpeningOfAnAccountProspectsThenFails(string $name): void
    {
        $this->prospectedByTheReaction = $id = new OrganizationId($this->application->newIdentity());
        $this->application->alsoReactWith(new FailingReaction(new \RuntimeException('The reaction failed'), function (AccountOpened $event) use ($id, $name): void {
            $founder = new Prospector(new MemberId($event->founderId), new AccountId($event->accountId));
            $this->application->organizations->save(Organization::prospectedBy($founder, $id, new LegalIdentity($name), new ContactDetails(), $event->occurredAt));
        }));
    }

    #[Then('l\'ouverture du compte attend en file d\'échec : :message')]
    public function theOpeningOfTheAccountWaitsInTheFailures(string $message): void
    {
        $failures = array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(AccountOpened::class));

        Expect::same([$message], $failures);
    }

    #[Then('aucune organisation :name n\'est prospectée')]
    public function noOrganizationIsProspected(string $name): void
    {
        $id = $this->prospectedByTheReaction ?? throw new \LogicException(sprintf('No reaction prospects %s', $name));

        Expect::same([], $this->application->organizations->ofIds([$id]));
    }

    #[Then('rien n\'est écrit')]
    public function nothingIsWritten(): void
    {
        Expect::that($this->application->database->isEmpty(), 'Something is written');
    }
}
