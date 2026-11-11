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
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\KeptAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\KeptExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\KeptOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

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
    /**
     * @var \Closure(?string): (KeptOrganizationFacts|KeptExchangeFacts|KeptContactFacts|KeptAccounts) the slice asked, given the cursor
     */
    private \Closure $askFacts;
    private KeptOrganizationFacts|KeptExchangeFacts|KeptContactFacts|KeptAccounts|null $facts = null;

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

    #[When('un autre contexte demande à Prospecting :max faits des organisations de :account')]
    public function anotherContextListsTheOrganizationFacts(int $max, string $account): void
    {
        $this->listOrganizationFacts($this->scenario->accountIdOf($account)->value, $max);
    }

    #[When('un autre contexte demande à Prospecting :max faits des organisations d\'un compte inconnu')]
    public function anotherContextListsTheOrganizationFactsOfAnUnknownAccount(int $max): void
    {
        $this->listOrganizationFacts($this->application->newIdentity(), $max);
    }

    #[When('un autre contexte demande à Prospecting :max faits des échanges de :account')]
    public function anotherContextListsTheExchangeFacts(int $max, string $account): void
    {
        $this->listExchangeFacts($this->scenario->accountIdOf($account)->value, $max);
    }

    #[When('un autre contexte demande à Prospecting :max faits des échanges d\'un compte inconnu')]
    public function anotherContextListsTheExchangeFactsOfAnUnknownAccount(int $max): void
    {
        $this->listExchangeFacts($this->application->newIdentity(), $max);
    }

    #[When('un autre contexte demande à PersonalData :max faits des contacts de :account')]
    public function anotherContextListsTheContactFacts(int $max, string $account): void
    {
        $this->listContactFacts($this->scenario->accountIdOf($account)->value, $max);
    }

    #[When('un autre contexte demande à PersonalData :max faits des contacts d\'un compte inconnu')]
    public function anotherContextListsTheContactFactsOfAnUnknownAccount(int $max): void
    {
        $this->listContactFacts($this->application->newIdentity(), $max);
    }

    #[When('/^un autre contexte demande à Accounts (?<max>\d+) comptes?$/')]
    public function anotherContextListsTheAccounts(int $max): void
    {
        $this->askFacts = fn (?string $after): KeptAccounts => $this->application->listAccounts(new ListAccounts($after, $max));
        $this->facts = ($this->askFacts)(null);
    }

    #[When('il demande la suite')]
    public function itAsksWhatFollows(): void
    {
        $this->facts = ($this->askFacts)($this->facts?->next);
    }

    #[Then('Prospecting répond les faits des organisations :')]
    public function prospectingAnswersTheOrganizationFacts(TableNode $table): void
    {
        $facts = $this->facts instanceof KeptOrganizationFacts ? $this->facts->facts : [];

        Expect::same($table->getColumnsHash(), array_map(fn (OrganizationFactsResponse $facts): array => [
            'organisation' => $this->scenario->nameOf(new OrganizationId($facts->organizationId)),
            'révision' => (string) $facts->revision,
            'présente' => Vocabulary::describeYesOrNo($facts->present),
            'géolocalisée' => Vocabulary::describeYesOrNo($facts->located),
        ], $facts));
    }

    #[Then('/^Prospecting répond les faits de (?<count>\d+) échanges?, (?:présents?), révision (?<revision>\d+)$/')]
    public function prospectingAnswersTheExchangeFacts(int $count, int $revision): void
    {
        $facts = $this->facts instanceof KeptExchangeFacts ? $this->facts->facts : [];

        Expect::same(array_fill(0, $count, ['present' => true, 'revision' => $revision]), array_map(static fn (ExchangeFactsResponse $facts): array => ['present' => $facts->present, 'revision' => $facts->revision], $facts));
    }

    #[Then('PersonalData répond les faits des contacts :')]
    public function personalDataAnswersTheContactFacts(TableNode $table): void
    {
        $facts = $this->facts instanceof KeptContactFacts ? $this->facts->facts : [];

        Expect::same($table->getColumnsHash(), array_map(fn (ContactFactsResponse $facts): array => [
            'contact' => $this->scenario->nameOfPerson(new PersonId($facts->personId)),
            'révision' => (string) $facts->revision,
            'présent' => Vocabulary::describeYesOrNo($facts->present),
        ], $facts));
    }

    #[Then('Accounts répond les comptes :')]
    public function accountsAnswersTheAccounts(TableNode $accounts): void
    {
        $accountIds = $this->facts instanceof KeptAccounts ? $this->facts->accountIds : [];

        Expect::same($accounts->getColumn(0), array_map(fn (string $accountId): string => $this->scenario->nameOf(new AccountId($accountId)), $accountIds));
    }

    #[Then('Prospecting ne donne pas de suite')]
    #[Then('PersonalData ne donne pas de suite')]
    #[Then('Accounts ne donne pas de suite')]
    public function theContextGivesNothingToFollow(): void
    {
        Expect::same(null, $this->facts?->next);
    }

    #[Then('Prospecting ne répond aucun fait')]
    #[Then('PersonalData ne répond aucun fait')]
    public function theContextAnswersNoFacts(): void
    {
        Expect::same([], $this->facts instanceof KeptAccounts ? null : $this->facts?->facts);
        $this->theContextGivesNothingToFollow();
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

    private function listOrganizationFacts(string $accountId, int $max): void
    {
        $this->askFacts = fn (?string $after): KeptOrganizationFacts => $this->application->listOrganizationFacts(new ListOrganizationFacts($accountId, $after, $max));
        $this->facts = ($this->askFacts)(null);
    }

    private function listExchangeFacts(string $accountId, int $max): void
    {
        $this->askFacts = fn (?string $after): KeptExchangeFacts => $this->application->listExchangeFacts(new ListExchangeFacts($accountId, $after, $max));
        $this->facts = ($this->askFacts)(null);
    }

    private function listContactFacts(string $accountId, int $max): void
    {
        $this->askFacts = fn (?string $after): KeptContactFacts => $this->application->listContactFacts(new ListContactFacts($accountId, $after, $max));
        $this->facts = ($this->askFacts)(null);
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
