<?php

namespace App\Security;

use App\Entity\User;
use App\Presenter\FindByUserNamePresenter;
use Solean\CleanProspecter\UseCase\FindByUserName\FindByUserNameRequest;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ProspecterUserProvider implements UserProviderInterface
{
    /**
     * @var UseCasesFacade
     */
    private $useCasesFacade;

    /**
     * @param UseCasesFacade $useCasesFacade
     */
    public function __construct(UseCasesFacade $useCasesFacade)
    {
        $this->useCasesFacade = $useCasesFacade;
    }

    /**
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function loadUserByUsername($username)
    {
        if ($response = $this->useCasesFacade->findByUserName(new FindByUserNameRequest($username, ''), new FindByUserNamePresenter())) {
            return new User($response);
        }

        throw new UsernameNotFoundException('Bad credentials');
    }

    /**
     * @param UserInterface $user
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
