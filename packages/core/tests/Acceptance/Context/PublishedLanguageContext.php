<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOf;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\IsProspectedBy;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;

/**
 * The contract a context publishes to the others (ADR 0045): what its published queries answer.
 */
final class PublishedLanguageContext implements Context
{
    use SharesTheScenario;

    /**
     * @var array<string, MemberId>
     */
    private array $members = [];
    private ?MemberResponse $member = null;

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

    #[When('un autre contexte demande à Accounts qui est le membre :name')]
    public function anotherContextConsultsTheMember(string $name): void
    {
        $this->member = $this->application->consultMember(new ConsultMember($this->memberIdOf($name)));
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

    #[Then('PersonalData répond que :person est un contact de :account')]
    public function personalDataAnswersAContact(string $person, string $account): void
    {
        Expect::same(true, $this->isAContactOf($person, $account));
    }

    #[Then('PersonalData répond que :person n\'est pas un contact de :account')]
    public function personalDataAnswersNoContact(string $person, string $account): void
    {
        Expect::same(false, $this->isAContactOf($person, $account));
    }

    #[When('un autre contexte demande à PersonalData si :person est un contact de :account')]
    public function anotherContextAsksPersonalData(string $person, string $account): void
    {
        $this->isAContactOf($person, $account);
    }

    #[Then('Prospecting répond que :organization est prospectée par :account')]
    public function prospectingAnswersProspected(string $organization, string $account): void
    {
        Expect::same(true, $this->isProspectedBy($organization, $account));
    }

    #[Then('Prospecting répond que :organization n\'est pas prospectée par :account')]
    public function prospectingAnswersNotProspected(string $organization, string $account): void
    {
        Expect::same(false, $this->isProspectedBy($organization, $account));
    }

    #[When('un autre contexte demande à Prospecting si :organization est prospectée par :account')]
    public function anotherContextAsksProspecting(string $organization, string $account): void
    {
        $this->isProspectedBy($organization, $account);
    }

    private function expectTheMember(string $name, string $account, bool $readsPersonalData): void
    {
        Expect::same(
            ['accountId' => $this->scenario->accountIdOf($account)->value, 'personId' => $this->scenario->personIdOf($name), 'readsPersonalData' => $readsPersonalData],
            ['accountId' => $this->member?->accountId, 'personId' => $this->member?->personId, 'readsPersonalData' => $this->member?->readsPersonalData],
        );
    }

    private function isAContactOf(string $person, string $account): ?bool
    {
        return $this->application->isAContactOf(new IsAContactOf($this->scenario->personIdOf($person), $this->scenario->accountIdOf($account)->value));
    }

    private function isProspectedBy(string $organization, string $account): ?bool
    {
        return $this->application->isProspectedBy(new IsProspectedBy($this->scenario->idOf($organization), $this->scenario->accountIdOf($account)->value));
    }

    /**
     * A member nobody named in the scenario has an identity nobody has.
     */
    private function memberIdOf(string $name): string
    {
        return isset($this->members[$name]) ? $this->members[$name]->value : $this->application->newIdentity();
    }
}
