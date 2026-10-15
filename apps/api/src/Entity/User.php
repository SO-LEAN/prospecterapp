<?php

namespace App\Entity;

use Prospecter\Core\Entity\User as CoreUser;
use Prospecter\Core\UseCase\UseCaseConsumer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The signed-in user, as the use cases see it: who it is, its organization and its roles.
 */
final readonly class User implements UserInterface, UseCaseConsumer
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private int $id,
        private string $userName,
        private int $organizationId,
        private array $roles,
    ) {
    }

    public static function from(CoreUser $user): self
    {
        return new self($user->getId(), $user->getUserName(), $user->getOrganization()->getId(), array_values($user->getRoles()));
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->userName;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    public function getUserId(): int
    {
        return $this->id;
    }
}
