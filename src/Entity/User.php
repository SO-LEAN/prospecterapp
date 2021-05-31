<?php

namespace App\Entity;

use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;
use Solean\CleanProspecter\UseCase\UseCaseConsumer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User.
 */
final class User implements UserInterface, UseCaseConsumer
{
    /**
     * @var RefreshUserResponse
     */
    private $refreshUser;

    /**
     * @param RefreshUserResponse|null $refreshUseronse
     */
    public function __construct(RefreshUserResponse $refreshUser = null)
    {
        $this->refreshUser = $refreshUser;
    }

    public function getRoles(): array
    {
        return $this->refreshUser->getRoles();
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->refreshUser->getPassword();
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->refreshUser->getUserName();
    }

    public function eraseCredentials()
    {
        return;
    }

    public function getOrganizationId()
    {
        return $this->refreshUser->getOrganizationId();
    }

    public function getUserId()
    {
        return $this->refreshUser->getId();
    }

    public function getPictureUrl()
    {
        return $this->refreshUser->getPictureUrl();
    }
}
