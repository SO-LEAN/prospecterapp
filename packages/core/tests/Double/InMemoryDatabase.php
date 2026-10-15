<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;

/**
 * The tables behind the in-memory repositories, with the constraints of the real ones: an email unique among
 * the persons of an account for a purpose, and among the organizations an account prospects. Like a database,
 * it keeps copies: a change is stored only when it is saved.
 */
final class InMemoryDatabase
{
    /** @var array<string, Account> */
    private array $accounts = [];
    /** @var array<string, Member> */
    private array $members = [];
    /** @var array<string, Person> */
    private array $persons = [];
    /** @var array<string, Organization> */
    private array $organizations = [];

    public function storeAccount(Account $account): void
    {
        $this->accounts[(string) $account->id()] = clone $account;
    }

    public function storeMember(Member $member): void
    {
        $this->members[(string) $member->id()] = clone $member;
    }

    public function storePerson(Person $person): void
    {
        $email = $person->contactDetails()->email;

        foreach ($this->persons as $id => $other) {
            if (null !== $email && $id !== $person->id()->value && $other->accountId()->equals($person->accountId()) && $other->purpose() === $person->purpose() && $other->contactDetails()->email?->value === $email->value) {
                throw EmailAlreadyUsed::byAnother();
            }
        }

        $this->persons[(string) $person->id()] = clone $person;
    }

    public function storeOrganization(Organization $organization): void
    {
        $email = $organization->contactDetails()->email;

        foreach ($this->organizations as $id => $other) {
            if (null !== $email && $id !== $organization->id()->value && $other->accountId()->equals($organization->accountId()) && $other->contactDetails()->email?->value === $email->value) {
                throw EmailAlreadyUsed::byAnother();
            }
        }

        $this->organizations[(string) $organization->id()] = clone $organization;
    }

    public function account(string $id): ?Account
    {
        return isset($this->accounts[$id]) ? clone $this->accounts[$id] : null;
    }

    public function member(string $id): ?Member
    {
        return isset($this->members[$id]) ? clone $this->members[$id] : null;
    }

    public function person(string $id): ?Person
    {
        return isset($this->persons[$id]) ? clone $this->persons[$id] : null;
    }

    public function organization(string $id): ?Organization
    {
        return isset($this->organizations[$id]) ? clone $this->organizations[$id] : null;
    }

    /**
     * @return list<Member>
     */
    public function members(): array
    {
        return array_values(array_map(static fn (Member $member): Member => clone $member, $this->members));
    }

    /**
     * @return list<Organization>
     */
    public function organizations(): array
    {
        return array_values(array_map(static fn (Organization $organization): Organization => clone $organization, $this->organizations));
    }
}
