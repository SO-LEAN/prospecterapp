<?php

namespace App\Entity;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\User as CoreUser;
use Prospecter\Core\Domain\User\UserId;
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
        private string $organizationId,
        private array $roles,
    ) {
    }

    public static function from(CoreUser $user): self
    {
        return new self(
            $user->id()->value,
            $user->userName(),
            $user->organizationId()->value,
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

    public function getUserId(): UserId
    {
        return new UserId($this->id);
    }

    public function getOrganizationId(): OrganizationId
    {
        return new OrganizationId($this->organizationId);
    }
}
