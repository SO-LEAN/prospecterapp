<?php

namespace App\Security;

use App\Entity\User;
use Prospecter\Core\Accounts\Domain\Member as CoreUser;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads the signed-in user from its user name (the subject of the access token) straight from the
 * repository: authentication is infrastructure, it does not go through a use case.
 *
 * @implements UserProviderInterface<User>
 */
final readonly class ProspecterUserProvider implements UserProviderInterface
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return User::from($this->find($identifier));
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

    public function find(string $userName): CoreUser
    {
        return $this->members->ofUserName($userName) ?? throw new UserNotFoundException('Bad credentials');
    }
}
