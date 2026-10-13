<?php

namespace App\Security;

use App\Entity\User;
use App\Presenter\RefreshUserPresenterImpl;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserRequest;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class ProspecterUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UseCasesFacade $useCasesFacade)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $response = $this->useCasesFacade->refreshUser(new RefreshUserRequest($identifier), new RefreshUserPresenterImpl());

        if (!$response) {
            throw new UserNotFoundException('Bad credentials');
        }

        return new User($response);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
