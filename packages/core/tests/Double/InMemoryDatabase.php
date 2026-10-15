<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The tables behind the in-memory repositories, with the constraints of the real ones: an email unique among
 * accounts, members and prospected organizations. Like a database, it keeps copies: a change is stored only
 * when it is saved.
 */
final class InMemoryDatabase
{
    public readonly TimeOrderedUuids $uuids;
    /** @var array<string, Account> */
    private array $accounts = [];
    /** @var array<string, Member> */
    private array $members = [];
    /** @var array<string, Organization> */
    private array $organizations = [];

    public function __construct()
    {
        $this->uuids = new TimeOrderedUuids();
    }

    public function storeAccount(Account $account): void
    {
        $this->assertEmailIsFree($account->contactDetails()->email, $account->id());
        $this->accounts[(string) $account->id()] = clone $account;
    }

    public function storeMember(Member $member): void
    {
        $this->assertEmailIsFree($member->contactDetails()->email, $member->id());
        $this->members[(string) $member->id()] = clone $member;
    }

    public function storeOrganization(Organization $organization): void
    {
        $this->assertEmailIsFree($organization->contactDetails()->email, $organization->id());
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

    private function assertEmailIsFree(?Email $email, Uuid $ownerId): void
    {
        if (null === $email) {
            return;
        }

        foreach ([...$this->accounts, ...$this->members, ...$this->organizations] as $id => $other) {
            if ($id !== $ownerId->value && $other->contactDetails()->email?->value === $email->value) {
                throw new EmailAlreadyUsed();
            }
        }
    }
}
