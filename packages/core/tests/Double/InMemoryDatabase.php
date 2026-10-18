<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

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
    /** @var array<string, Exchange> */
    private array $exchanges = [];

    public function storeExchange(Exchange $exchange): void
    {
        $this->exchanges[(string) $exchange->id()] = self::withoutItsEvents($exchange);
    }

    public function forgetExchange(string $id): void
    {
        unset($this->exchanges[$id]);
    }

    /**
     * @return list<Exchange>
     */
    public function exchanges(): array
    {
        return array_values(array_map(static fn (Exchange $exchange): Exchange => clone $exchange, $this->exchanges));
    }

    public function storeAccount(Account $account): void
    {
        $this->accounts[(string) $account->id()] = self::withoutItsEvents($account);
    }

    public function storeMember(Member $member): void
    {
        $this->members[(string) $member->id()] = clone $member;
    }

    public function storePerson(Person $person): void
    {
        $this->persons[(string) $person->id()] = self::withoutItsEvents($person);
    }

    public function forgetPerson(string $id): void
    {
        unset($this->persons[$id]);
    }

    public function forgetOrganization(string $id): void
    {
        unset($this->organizations[$id]);
    }

    public function storeOrganization(Organization $organization): void
    {
        $this->organizations[(string) $organization->id()] = self::withoutItsEvents($organization);
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
     * @return list<Person>
     */
    public function persons(): array
    {
        return array_values(array_map(static fn (Person $person): Person => clone $person, $this->persons));
    }

    /**
     * @return list<Organization>
     */
    public function organizations(): array
    {
        return array_values(array_map(static fn (Organization $organization): Organization => clone $organization, $this->organizations));
    }

    /**
     * Like the DynamoDB adapters, a stored aggregate keeps its state, not the events it recorded: they belong to
     * the use case that changed it.
     *
     * @template T of ReleasesEvents
     *
     * @param T $aggregate
     *
     * @return T
     */
    private static function withoutItsEvents(ReleasesEvents $aggregate): ReleasesEvents
    {
        $stored = clone $aggregate;
        $stored->releaseEvents();

        return $stored;
    }
}
