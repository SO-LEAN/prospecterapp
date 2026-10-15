<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\EmailAlreadyUsed;
use Prospecter\Core\Domain\User\User;

/**
 * The tables behind the in-memory repositories, with the constraints of the real ones: an email unique among
 * organizations and users. Like a database, it keeps copies: a change is stored only when it is saved.
 */
final class InMemoryDatabase
{
    public readonly TimeOrderedUuids $uuids;

    /** @var array<string, Organization> */
    private array $organizations = [];
    /** @var array<string, User> */
    private array $users = [];

    public function __construct()
    {
        $this->uuids = new TimeOrderedUuids();
    }

    public function storeOrganization(Organization $organization): void
    {
        $this->assertEmailIsFree($organization->contactDetails()->email, (string) $organization->id());
        $this->organizations[(string) $organization->id()] = clone $organization;
    }

    public function storeUser(User $user): void
    {
        $this->assertEmailIsFree($user->contactDetails()->email, (string) $user->id());
        $this->users[(string) $user->id()] = clone $user;
    }

    public function organization(string $id): ?Organization
    {
        return isset($this->organizations[$id]) ? clone $this->organizations[$id] : null;
    }

    public function user(string $id): ?User
    {
        return isset($this->users[$id]) ? clone $this->users[$id] : null;
    }

    /**
     * @return list<Organization>
     */
    public function organizations(): array
    {
        return array_values(array_map(static fn (Organization $organization): Organization => clone $organization, $this->organizations));
    }

    /**
     * @return list<User>
     */
    public function users(): array
    {
        return array_values(array_map(static fn (User $user): User => clone $user, $this->users));
    }

    private function assertEmailIsFree(?Email $email, string $ownerId): void
    {
        if (null === $email) {
            return;
        }

        foreach ([...$this->organizations, ...$this->users] as $id => $other) {
            if ($id !== $ownerId && $other->contactDetails()->email?->value === $email->value) {
                throw new EmailAlreadyUsed();
            }
        }
    }
}
