<?php

namespace App\Entity;

use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;
use Solean\CleanProspecter\UseCase\UseCaseConsumer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security user built from the RefreshUser use case response.
 */
final readonly class User implements UserInterface, UseCaseConsumer
{
    public function __construct(private RefreshUserResponse $refreshUser)
    {
    }

    public function getRoles(): array
    {
        return $this->refreshUser->getRoles();
    }

    public function getPassword(): string
    {
        return $this->refreshUser->getPassword();
    }

    public function getUserIdentifier(): string
    {
        return $this->refreshUser->getUserName();
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getOrganizationId(): mixed
    {
        return $this->refreshUser->getOrganizationId();
    }

    public function getUserId(): mixed
    {
        return $this->refreshUser->getId();
    }

    public function getPictureUrl(): ?string
    {
        return $this->refreshUser->getPictureUrl();
    }
}
