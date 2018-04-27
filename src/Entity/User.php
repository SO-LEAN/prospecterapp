<?php

namespace App\Entity;

use Solean\CleanProspecter\UseCase\UseCaseConsumer;
use Symfony\Component\Security\Core\User\UserInterface;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;

/**
 * Class User.
 */
final class User implements UserInterface, UseCaseConsumer
{
    /**
     * @var RefreshUserResponse
     */
    private $loginResponse;

    /**
     * @param RefreshUserResponse|null $loginResponse
     */
    public function __construct(RefreshUserResponse $loginResponse = null)
    {
        $this->loginResponse = $loginResponse;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->loginResponse->getRoles();
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->loginResponse->getPassword();
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
        return $this->loginResponse->getUserName();
    }

    public function eraseCredentials()
    {
        return;
    }

    public function getOrganizationId()
    {
        return $this->loginResponse->getOrganizationId();
    }

    public function getUserId()
    {
        return $this->loginResponse->getId();
    }
}
