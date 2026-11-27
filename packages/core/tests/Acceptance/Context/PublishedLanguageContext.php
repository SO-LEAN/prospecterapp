<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Application\Reminders\ChangeMyReminders\ChangeMyReminders;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\KeptAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendar;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\ContactNameResponse;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\MemberPresentationResponse;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContacts;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\CandidateResponse;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\IdentificationResponse;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\OrganizationClues;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\KeptExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\KeptOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\OrganizationsOfContactResponse;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\OrganizationsNamingContactResponse;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContacts;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The contract a context publishes to the others (ADR 0045, 0089): what its services answer about a set, what is
 * unknown being absent.
 */
final class PublishedLanguageContext implements Context
{
    use SharesTheScenario;

    private const array IDENTIFICATION_STATUSES = [IdentificationResponse::KNOWN => 'connue', IdentificationResponse::NEW => 'nouvelle', IdentificationResponse::TO_CONFIRM => 'à confirmer'];

    /**
     * @var array<string, MemberId>
     */
    private array $members = [];

    /** @var list<IdentificationResponse> */
    private array $identifications = [];
    /**
     * @var array<string, MemberResponse>
     */
    private array $consulted = [];
    /**
     * @var list<string>
     */
    private array $answered = [];
    /**
     * @var list<MemberPresentationResponse>
     */
    private array $presented = [];
    /**
     * @var array<string, string> the names asked about, by their identity
     */
    private array $asked = [];
    /**
     * @var \Closure(?string): (KeptOrganizationFacts|KeptExchangeFacts|KeptContactFacts|KeptAccounts) the slice asked, given the cursor
     */
    private \Closure $askFacts;
    private KeptOrganizationFacts|KeptExchangeFacts|KeptContactFacts|KeptAccounts|null $facts = null;
    /**
     * @var list<ContactNameResponse>
     */
    private array $names = [];
    /**
     * @var list<HeldExchangeResponse>
     */
    private array $heldExchanges = [];
    /**
     * @var list<OrganizationsOfContactResponse>
     */
    private array $organizationsOfContacts = [];
    /**
     * @var array<string, string> the identity having each address asked, by the address
     */
    private array $byEmail = [];

    /**
     * @var array<string, ?string> the address of each one answered, by its identity
     */
    private array $emailsOf = [];

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

    #[Given(':name est membre de :account qui l\'administre')]
    public function anAdministrator(string $name, string $account): void
    {
        $this->members[$name] = $this->scenario->aMemberOf($name, $account, ...Role::cases())->id();
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

    #[When('un autre contexte demande à PersonalData de présenter :')]
    public function anotherContextAsksPersonalDataToPresent(TableNode $persons): void
    {
        $this->presented = $this->application->presentMembers(new PresentMembers(array_map($this->scenario->personIdOf(...), $persons->getColumn(0))));
    }

    #[Then('PersonalData présente seulement :name, avec la photo :picture')]
    public function personalDataPresentsOnly(string $name, string $picture): void
    {
        Expect::same([[$name, $picture]], array_map(static fn (MemberPresentationResponse $presented): array => [trim(sprintf('%s %s', $presented->firstName, $presented->lastName)), $presented->pictureUrl], $this->presented));
    }

    #[Then('Accounts répond que :name est membre du compte nommé :account')]
    public function accountsAnswersTheNameOfTheAccount(string $name, string $account): void
    {
        Expect::same($account, ($this->consulted[$this->memberIdOf($name)] ?? null)?->accountName);
    }

    #[Then('Accounts répond que :name administre son compte')]
    public function accountsAnswersAnAdministrator(string $name): void
    {
        Expect::same(true, $this->consulted[$this->memberIdOf($name)]->administers ?? null);
    }

    #[Then('Accounts répond que :name n\'administre pas son compte')]
    public function accountsAnswersAMemberNotAdministering(string $name): void
    {
        Expect::same(false, $this->consulted[$this->memberIdOf($name)]->administers ?? null);
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

    #[When('un autre contexte demande à PersonalData quels contacts de :account ont les adresses :')]
    public function anotherContextAsksPersonalDataByEmail(string $account, TableNode $emails): void
    {
        $this->byEmail = $this->application->whichContactsHaveTheseEmails(new WhichContactsHaveTheseEmails($emails->getColumn(0), $this->scenario->accountIdOf($account)->value));
    }

    #[Then('PersonalData répond les contacts :')]
    public function personalDataAnswersTheContactsByEmail(TableNode $expected): void
    {
        Expect::same($expected->getColumnsHash(), $this->describeByEmail('contact', fn (string $id): string => $this->scenario->nameOfPerson(new PersonId($id))));
    }

    #[Then('PersonalData ne répond aucun contact')]
    public function personalDataAnswersNoContactByEmail(): void
    {
        Expect::same([], $this->byEmail);
    }

    #[When('un autre contexte demande à PersonalData les adresses e-mail, comme :reader les lit, de :')]
    public function anotherContextAsksTheEmailsOfContacts(string $reader, TableNode $contacts): void
    {
        $this->emailsOf = $this->application->whichEmailsHaveTheseContacts(new WhichEmailsHaveTheseContacts($this->memberIdOf($reader), array_map($this->scenario->personIdOf(...), $contacts->getColumn(0))));
    }

    #[Then('PersonalData répond les adresses e-mail :')]
    public function personalDataAnswersTheEmailsOfContacts(TableNode $expected): void
    {
        Expect::same($expected->getColumnsHash(), $this->describeEmailsOf('contact', fn (string $id): string => $this->scenario->nameOfPerson(new PersonId($id))));
    }

    #[When('un autre contexte demande à Prospecting les adresses e-mail, pour :account, de :')]
    public function anotherContextAsksTheEmailsOfOrganizations(string $account, TableNode $organizations): void
    {
        $this->emailsOf = $this->application->whichEmailsHaveTheseOrganizations(new WhichEmailsHaveTheseOrganizations(array_map($this->scenario->idOf(...), $organizations->getColumn(0)), $this->scenario->accountIdOf($account)->value));
    }

    #[Then('Prospecting répond les adresses e-mail :')]
    public function prospectingAnswersTheEmailsOfOrganizations(TableNode $expected): void
    {
        Expect::same($expected->getColumnsHash(), $this->describeEmailsOf('organisation', fn (string $id): string => $this->scenario->nameOf(new OrganizationId($id))));
    }

    #[Then('/^(?:PersonalData|Prospecting) ne répond aucune adresse e-mail$/')]
    public function noEmailIsAnswered(): void
    {
        Expect::same([], $this->emailsOf);
    }

    /**
     * Each one answered with its address, none written empty, in the order of their names.
     *
     * @param \Closure(string): string $nameOf
     *
     * @return list<array<string, string>>
     */
    private function describeEmailsOf(string $column, \Closure $nameOf): array
    {
        $described = array_map(static fn (string $id, ?string $email): array => [$column => $nameOf($id), 'adresse' => $email ?? ''], array_keys($this->emailsOf), $this->emailsOf);
        usort($described, static fn (array $one, array $other): int => strcmp($one[$column], $other[$column]));

        return $described;
    }

    #[When('un autre contexte demande à Prospecting quelles organisations de :account ont les adresses :')]
    public function anotherContextAsksProspectingByEmail(string $account, TableNode $emails): void
    {
        $this->byEmail = $this->application->whichOrganizationsHaveTheseEmails(new WhichOrganizationsHaveTheseEmails($emails->getColumn(0), $this->scenario->accountIdOf($account)->value));
    }

    #[Then('Prospecting répond les organisations ayant ces adresses :')]
    public function prospectingAnswersTheOrganizationsByEmail(TableNode $expected): void
    {
        Expect::same($expected->getColumnsHash(), $this->describeByEmail('organisation', fn (string $id): string => $this->scenario->nameOf(new OrganizationId($id))));
    }

    /**
     * @param \Closure(string): string $nameOf
     *
     * @return list<array<string, string>>
     */
    private function describeByEmail(string $column, \Closure $nameOf): array
    {
        return array_map(static fn (string $email, string $id): array => ['adresse' => $email, $column => $nameOf($id)], array_keys($this->byEmail), $this->byEmail);
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
            'contacts' => implode(', ', array_map(fn (string $id): string => $this->scenario->nameOfPerson(new PersonId($id)), $facts->contacts)),
        ], $facts));
    }

    #[Then('/^Prospecting répond les faits de (?<count>\d+) échanges?, (?:présents?), (?:faits?), révision (?<revision>\d+)$/')]
    public function prospectingAnswersTheExchangeFacts(int $count, int $revision): void
    {
        $facts = $this->facts instanceof KeptExchangeFacts ? $this->facts->facts : [];

        Expect::same(array_fill(0, $count, ['present' => true, 'status' => 'done', 'revision' => $revision]), array_map(static fn (ExchangeFactsResponse $facts): array => ['present' => $facts->present, 'status' => $facts->status, 'revision' => $facts->revision], $facts));
    }

    #[Then('PersonalData répond les faits des contacts :')]
    public function personalDataAnswersTheContactFacts(TableNode $table): void
    {
        $facts = $this->facts instanceof KeptContactFacts ? $this->facts->facts : [];

        Expect::same($table->getColumnsHash(), array_map(fn (ContactFactsResponse $facts): array => [
            'contact' => $this->scenario->nameOfPerson(new PersonId($facts->personId)),
            'révision' => (string) $facts->revision,
            'présent' => Vocabulary::describeYesOrNo($facts->present),
            'joignable par' => Vocabulary::describeReach($facts->hasEmail, $facts->hasPhone, $facts->hasMobile, $facts->hasLinkedIn),
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

    #[When('un autre contexte demande à Connectors lesquels ont un agenda connecté :')]
    public function anotherContextAsksConnectors(TableNode $members): void
    {
        $this->asked = array_combine(array_map($this->memberIdOf(...), $members->getColumn(0)), $members->getColumn(0));
        $this->answered = $this->application->whichHaveAConnectedCalendar(new WhichHaveAConnectedCalendar(array_keys($this->asked)));
    }

    #[Then('PersonalData répond :')]
    #[Then('Prospecting répond :')]
    #[Then('Connectors répond :')]
    public function theContextAnswers(TableNode $names): void
    {
        Expect::same($names->getColumn(0), $this->namesAnswered());
    }

    #[Then('PersonalData ne répond aucune')]
    #[Then('Prospecting ne répond aucune')]
    #[Then('Connectors ne répond aucun')]
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
    #[Given(':name a réglé ses rappels à :minutes minutes avant')]
    public function aMemberSetTheirReminders(string $name, int $minutes): void
    {
        $this->application->dispatch(new ChangeMyReminders($this->memberIdOf($name), true, $minutes, $this->application->now()));
    }

    #[Given(':name a désactivé ses rappels')]
    public function aMemberTurnedTheirRemindersOff(string $name): void
    {
        $this->application->dispatch(new ChangeMyReminders($this->memberIdOf($name), false, 10, $this->application->now()));
    }

    #[Then('Accounts répond que :name veut être rappelée :minutes minutes avant')]
    #[Then('Accounts répond que :name veut être rappelé :minutes minutes avant')]
    public function accountsAnswersTheReminderOf(string $name, int $minutes): void
    {
        Expect::same($minutes, $this->consulted[$this->memberIdOf($name)]->reminderMinutesBefore ?? null);
    }

    #[Then('Accounts répond que :name ne veut pas être rappelé')]
    #[Then('Accounts répond que :name ne veut pas être rappelée')]
    public function accountsAnswersNoReminderOf(string $name): void
    {
        $member = $this->consulted[$this->memberIdOf($name)] ?? null;
        $member = Expect::present($member, sprintf('Accounts did not answer about "%s"', $name));
        Expect::same(null, $member->reminderMinutesBefore);
    }

    #[When('un autre contexte demande à PersonalData les noms, comme :reader les lit, de :')]
    public function anotherContextAsksTheNames(string $reader, TableNode $persons): void
    {
        $this->names = $this->application->nameContacts(new NameContacts($this->memberIdOf($reader), array_map($this->scenario->personIdOf(...), $persons->getColumn(0))));
    }

    #[Then('PersonalData répond les noms :')]
    public function personalDataAnswersTheNames(TableNode $names): void
    {
        Expect::same($names->getColumn(0), array_map(static fn (ContactNameResponse $name): string => trim(sprintf('%s %s', $name->firstName, $name->lastName)), $this->names));
    }

    #[Then('PersonalData répond que :name est joignable à :email')]
    public function personalDataAnswersTheEmail(string $name, string $email): void
    {
        Expect::same($email, $this->nameAnsweredFor($name)?->email);
    }

    #[Then('PersonalData répond que :name est joignable au :phone')]
    public function personalDataAnswersThePhone(string $name, string $phone): void
    {
        Expect::same($phone, $this->nameAnsweredFor($name)?->phoneNumber);
    }

    #[Then('PersonalData ne répond ni l\'e-mail ni le téléphone de :name')]
    public function personalDataAnswersNoContactDetails(string $name): void
    {
        $answered = $this->nameAnsweredFor($name);
        Expect::same([true, null, null], [null !== $answered, $answered?->email, $answered?->phoneNumber]);
    }

    private function nameAnsweredFor(string $name): ?ContactNameResponse
    {
        $person = $this->scenario->personIdOf($name);

        return array_find($this->names, static fn (ContactNameResponse $named): bool => $named->personId === $person);
    }

    #[Then('PersonalData répond que :name est :jobTitle')]
    public function personalDataAnswersTheJobTitle(string $name, string $jobTitle): void
    {
        $person = $this->scenario->personIdOf($name);
        $answered = array_find($this->names, static fn (ContactNameResponse $named): bool => $named->personId === $person);
        Expect::same($jobTitle, $answered?->jobTitle);
    }

    #[Then('PersonalData répond que :name a la civilité :civility')]
    public function personalDataAnswersTheCivility(string $name, string $civility): void
    {
        $person = $this->scenario->personIdOf($name);
        $answered = array_find($this->names, static fn (ContactNameResponse $named): bool => $named->personId === $person);
        Expect::same($civility, $answered?->civility);
    }

    #[When('un autre contexte demande à Prospecting, pour :member, quelles organisations désignent les noms :')]
    public function anotherContextAsksWhichOrganizationsTheNamesDesignate(string $member, TableNode $names): void
    {
        $clues = array_map(static fn (string $name): OrganizationClues => new OrganizationClues(name: $name), $names->getColumn(0));
        $this->identifications = $this->application->identifyOrganizationsForAnotherContext(new IdentifyOrganizations($this->memberIdOf($member), $clues));
    }

    #[Then('Prospecting répond les identifications :')]
    public function prospectingAnswersTheIdentifications(TableNode $table): void
    {
        Expect::same($table->getColumnsHash(), array_map(static fn (IdentificationResponse $identification): array => [
            'statut' => self::IDENTIFICATION_STATUSES[$identification->status],
            'candidates' => implode(', ', array_map(static fn (CandidateResponse $candidate): string => $candidate->name, $identification->candidates)),
        ], $this->identifications));
    }

    #[When('un autre contexte demande à Prospecting les organisations, pour :account, de :')]
    public function anotherContextAsksTheOrganizationsOfContacts(string $account, TableNode $contacts): void
    {
        $this->asked = array_combine(array_map($this->scenario->personIdOf(...), $contacts->getColumn(0)), $contacts->getColumn(0));
        $this->organizationsOfContacts = $this->application->nameOrganizationsOfContacts(new NameOrganizationsOfContacts($this->scenario->accountIdOf($account)->value, array_keys($this->asked)));
    }

    /**
     * The identities answered, named as the organizations of the scenario, to read as the names answered.
     */
    #[When('un autre contexte demande à Prospecting quelles organisations, pour :account, nomment :')]
    public function anotherContextAsksWhichOrganizationsNameContacts(string $account, TableNode $contacts): void
    {
        $this->asked = array_combine(array_map($this->scenario->personIdOf(...), $contacts->getColumn(0)), $contacts->getColumn(0));
        $answered = $this->application->whichOrganizationsNameTheseContacts(new WhichOrganizationsNameTheseContacts($this->scenario->accountIdOf($account)->value, array_keys($this->asked)));
        $this->organizationsOfContacts = array_map(fn (OrganizationsNamingContactResponse $contact): OrganizationsOfContactResponse => new OrganizationsOfContactResponse(
            $contact->personId,
            array_map(fn (string $id): string => $this->scenario->nameOf(new OrganizationId($id)), $contact->organizationIds),
        ), $answered);
    }

    #[Then('Prospecting répond les organisations :')]
    public function prospectingAnswersTheOrganizations(TableNode $table): void
    {
        $answered = array_map(fn (OrganizationsOfContactResponse $contact): array => [
            'contact' => $this->asked[$contact->personId] ?? $contact->personId,
            'organisations' => implode(', ', $contact->organizationNames),
        ], $this->organizationsOfContacts);

        Expect::same($table->getColumnsHash(), $answered);
    }

    #[Then('Prospecting ne répond aucune organisation')]
    public function prospectingAnswersNoOrganization(): void
    {
        Expect::same([[], []], [$this->organizationsOfContacts, $this->byEmail]);
    }

    #[Then('PersonalData ne répond aucun nom')]
    public function personalDataAnswersNoName(): void
    {
        Expect::same([], $this->names);
    }

    #[When('un autre contexte demande à Prospecting, de :account, cet échange et un échange inconnu')]
    public function anotherContextAsksTheHeldExchanges(string $account): void
    {
        $this->heldExchanges = $this->application->consultHeldExchanges(new ConsultHeldExchanges($this->scenario->accountIdOf($account)->value, [$this->scenario->theExchange(), $this->application->newIdentity()]));
    }

    #[When('un autre contexte demande à Prospecting, de :account, les échanges que je tiens à venir')]
    public function anotherContextAsksMyUpcomingExchanges(string $account): void
    {
        $this->heldExchanges = $this->application->consultPlannedExchanges(new ConsultPlannedExchanges($this->scenario->accountIdOf($account)->value, $this->application->signedIn(), $this->application->now()));
    }

    #[Then('Prospecting répond l\'échange :')]
    #[Then('Prospecting répond les échanges :')]
    public function prospectingAnswersTheExchange(TableNode $exchanges): void
    {
        $expected = array_map(fn (array $row): array => [...$row, 'titulaire' => 'moi' === $row['titulaire'] ? $this->application->signedIn() : $row['titulaire']], $exchanges->getColumnsHash());

        Expect::same($expected, array_map(fn (HeldExchangeResponse $exchange): array => [
            'titulaire' => $exchange->holderId,
            'statut' => Vocabulary::describeStatus($exchange->status),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'moment' => $exchange->startsAt->format('d/m/Y H:i'),
            'durée' => sprintf('%d min', $exchange->durationInMinutes),
            'lieu' => (string) $exchange->place,
            'lien' => (string) $exchange->link,
            'contact' => null === $exchange->contactId ? '' : $this->scenario->nameOfPerson(new PersonId($exchange->contactId)),
            'organisation' => (string) $exchange->organizationName,
        ], $this->heldExchanges));
    }

    /**
     * As between the commit of a stop of the prospecting and the reaction that stops keeping its exchanges for it.
     */
    #[Given(':organization n\'est plus prospectée, l\'échange encore gardé pour elle')]
    public function anOrganizationIsGoneBeforeItsExchanges(string $organization): void
    {
        $this->application->organizations->remove($this->scenario->organizationNamed($organization));
    }

    #[Then('Prospecting répond que cet échange demande son lien')]
    public function prospectingAnswersTheExchangeRequestsItsLink(): void
    {
        Expect::same([true], array_map(static fn (HeldExchangeResponse $exchange): bool => $exchange->linkRequested, $this->heldExchanges));
    }

    #[Then('Prospecting répond que cet échange a été réservé par le lien public')]
    public function prospectingAnswersTheExchangeWasBooked(): void
    {
        Expect::same([true], array_map(static fn (HeldExchangeResponse $exchange): bool => $exchange->bookedByLink, $this->heldExchanges));
    }

    #[Then('Prospecting ne répond aucun échange')]
    public function prospectingAnswersNoExchange(): void
    {
        Expect::same([], $this->heldExchanges);
    }

    private function memberIdOf(string $name): string
    {
        return ($this->members[$name] ?? $this->scenario->findMemberId($name))->value ?? $this->application->newIdentity();
    }
}
