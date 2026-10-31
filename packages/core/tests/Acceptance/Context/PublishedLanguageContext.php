<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;

/**
 * The contract a context publishes to the others (ADR 0045, 0089): what its services answer about a set, what is
 * unknown being absent.
 */
final class PublishedLanguageContext implements Context
{
    use SharesTheScenario;

    /**
     * @var array<string, MemberId>
     */
    private array $members = [];
    /**
     * @var array<string, MemberResponse>
     */
    private array $consulted = [];
    /**
     * @var list<string>
     */
    private array $answered = [];
    /**
     * @var array<string, string> the names asked about, by their identity
     */
    private array $asked = [];

    #[Given(':name est membre de :account avec le droit aux données personnelles')]
    public function aMemberManagingPersonalData(string $name, string $account): void
    {
        $this->members[$name] = $this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector, Role::PersonalData)->id();
    }

    #[Given(':name est membre de :account sans le droit aux données personnelles')]
    public function aMemberNotManagingPersonalData(string $name, string $account): void
    {
        $this->members[$name] = $this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector)->id();
    }

    #[When('un autre contexte demande à Accounts qui sont les membres :')]
    public function anotherContextConsultsTheMembers(TableNode $names): void
    {
        $this->asked = array_combine(array_map($this->memberIdOf(...), $names->getColumn(0)), $names->getColumn(0));
        $this->consulted = $this->application->consultMembers(new ConsultMembers(array_keys($this->asked)));
    }

    #[Then('Accounts répond que :name est membre de :account avec le droit aux données personnelles')]
    public function accountsAnswersAMemberManagingPersonalData(string $name, string $account): void
    {
        $this->expectTheMember($name, $account, true);
    }

    #[Then('Accounts répond que :name est membre de :account sans le droit aux données personnelles')]
    public function accountsAnswersAMemberNotManagingPersonalData(string $name, string $account): void
    {
        $this->expectTheMember($name, $account, false);
    }

    #[Then('Accounts ne répond rien de :name')]
    public function accountsAnswersNothingOf(string $name): void
    {
        Expect::that(!isset($this->consulted[(string) array_search($name, $this->asked, true)]), sprintf('Accounts answered about "%s"', $name));
    }

    #[When('un autre contexte demande à PersonalData lesquelles sont des contacts de :account :')]
    public function anotherContextAsksPersonalData(string $account, TableNode $persons): void
    {
        $this->asked = array_combine(array_map($this->scenario->personIdOf(...), $persons->getColumn(0)), $persons->getColumn(0));
        $this->answered = $this->application->whichAreContacts(new WhichAreContacts(array_keys($this->asked), $this->scenario->accountIdOf($account)->value));
    }

    #[When('un autre contexte demande à Prospecting lesquelles sont prospectées par :account :')]
    public function anotherContextAsksProspecting(string $account, TableNode $organizations): void
    {
        $this->asked = array_combine(array_map($this->scenario->idOf(...), $organizations->getColumn(0)), $organizations->getColumn(0));
        $this->answered = $this->application->whichAreProspected(new WhichAreProspected(array_keys($this->asked), $this->scenario->accountIdOf($account)->value));
    }

    #[Then('PersonalData répond :')]
    #[Then('Prospecting répond :')]
    public function theContextAnswers(TableNode $names): void
    {
        Expect::same($names->getColumn(0), $this->namesAnswered());
    }

    #[Then('PersonalData ne répond aucune')]
    #[Then('Prospecting ne répond aucune')]
    public function theContextAnswersNone(): void
    {
        Expect::same([], $this->namesAnswered());
    }

    private function expectTheMember(string $name, string $account, bool $readsPersonalData): void
    {
        $member = $this->consulted[$this->memberIdOf($name)] ?? null;

        Expect::same(
            ['accountId' => $this->scenario->accountIdOf($account)->value, 'personId' => $this->scenario->personIdOf($name), 'readsPersonalData' => $readsPersonalData],
            ['accountId' => $member?->accountId, 'personId' => $member?->personId, 'readsPersonalData' => $member?->readsPersonalData],
        );
    }

    /**
     * @return list<string>
     */
    private function namesAnswered(): array
    {
        return array_map(fn (string $id): string => $this->asked[$id], $this->answered);
    }

    /**
     * A member nobody named in the scenario has an identity nobody has.
     */
    private function memberIdOf(string $name): string
    {
        return isset($this->members[$name]) ? $this->members[$name]->value : $this->application->newIdentity();
    }
}
