<?php

namespace App\Entity;

use Solean\CleanProspecter\UseCase\FindByUserName\FindByUserNameResponse;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User.
 */
final class User implements UserInterface
{
    /**
     * @var FindByUserNameResponse
     */
    private $loginResponse;

    public function __construct(FindByUserNameResponse $loginResponse = null)
    {
        $this->loginResponse = $loginResponse;
    }

    /**
     * @return array
     */
    public function getRoles()
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
}
