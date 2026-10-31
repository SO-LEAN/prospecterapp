<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Application\RegisterAMember\RegisterAMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * The members of an account: who registers them, with which roles, under which profile.
 */
final class MemberContext implements Context
{
    use SharesTheScenario;

    #[Given(':name est administrateur de :account')]
    public function anAdministrator(string $name, string $account): void
    {
        $this->scenario->aMemberOf($name, $account, ...Role::cases());
    }

    /**
     * As a client registers a member: the member, then their profile filled in by the new member themselves.
     */
    #[When('j\'inscris :name à mon compte :')]
    public function iRegisterAMember(string $name, TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $member = $this->application->newIdentity();
        $profile = $this->application->newIdentity();
        $this->application->dispatch(new RegisterAMember($this->application->signedIn(), $member, $profile, Vocabulary::roles($values['rôles']), $this->application->now()));

        if (null === $this->application->refusal()) {
            $this->scenario->rememberMember($name, new MemberId($member));
            $this->scenario->rememberPerson($name, new PersonId($profile));
            $this->application->dispatch(new RenamePerson($member, $profile, $values['prénom'], $values['nom'], $this->application->now()));
            $this->application->dispatch(new ChangePersonContactDetails($member, $profile, $values['e-mail'], null, null, $this->application->now()));
        }
    }

    #[Then(':name est membre de :account avec les rôles :roles')]
    public function aMemberWithTheRoles(string $name, string $account, string $roles): void
    {
        $member = $this->scenario->memberNamed($name);

        Expect::same($account, $this->scenario->nameOf($member->accountId()));
        Expect::same($roles, implode(', ', array_map(static fn (Role $role): string => $role->value, StateOf::the($member)['roles'])));
    }

    #[Then(':name a le droit aux données personnelles')]
    public function readsPersonalData(string $name): void
    {
        Expect::same(true, $this->hasThePersonalDataRight($name));
    }

    #[Then(':name n\'a pas le droit aux données personnelles')]
    public function readsNoPersonalData(string $name): void
    {
        Expect::same(false, $this->hasThePersonalDataRight($name));
    }

    #[Then('le profil de :name est :')]
    public function theProfileOf(string $name, TableNode $table): void
    {
        $profile = $this->application->persons->get($this->scenario->profileOf($this->scenario->memberNamed($name)));

        Expect::describes($table->getRowsHash(), [
            'prénom' => (string) StateOf::the($profile)['name']->firstName,
            'nom' => (string) StateOf::the($profile)['name']->lastName,
            'e-mail' => (string) $profile->contactDetails()->email,
        ]);
    }

    #[Then('le profil de :name n\'est pas celui de :other')]
    public function theProfilesDiffer(string $name, string $other): void
    {
        Expect::that(!$this->scenario->profileOf($this->scenario->memberNamed($name))->equals($this->scenario->profileOf($this->scenario->memberNamed($other))), sprintf('%s and %s share a profile', $name, $other));
    }

    /**
     * As the other contexts learn it, by the published query of Accounts.
     */
    private function hasThePersonalDataRight(string $name): bool
    {
        $id = $this->scenario->memberNamed($name)->id()->value;

        return $this->application->consultMembers(new ConsultMembers([$id]))[$id]->readsPersonalData ?? false;
    }
}
