<?php

namespace App\Entity;

use Prospecter\Core\Accounts\Domain\Member as CoreUser;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The signed-in user as Symfony security sees it: its user name, its roles, and its id for the requests.
 */
final readonly class User implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $id,
        private string $userName,
        private string $accountId,
        private string $personId,
        private array $roles,
    ) {
    }

    public static function from(CoreUser $user): self
    {
        return new self(
            $user->id()->value,
            $user->userName(),
            $user->accountId()->value,
            $user->personId()->value,
            array_map(static fn (Role $role): string => $role->value, $user->roles()),
        );
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->userName;
    }

    public function memberId(): MemberId
    {
        return new MemberId($this->id);
    }

    public function accountId(): AccountId
    {
        return new AccountId($this->accountId);
    }

    public function personId(): PersonId
    {
        return new PersonId($this->personId);
    }
}
