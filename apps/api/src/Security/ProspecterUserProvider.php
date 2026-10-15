<?php

namespace App\Security;

use App\Auth\LoginEmails;
use App\Entity\User;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\UnknownMember;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
final readonly class ProspecterUserProvider implements UserProviderInterface
{
    public function __construct(private LoginEmails $loginEmails, private MemberRepository $members)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return User::from($this->find($identifier), $identifier);
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

    /**
     * The member signing in with this login email.
     */
    public function find(string $loginEmail): Member
    {
        try {
            return $this->members->get($this->loginEmails->memberOf($loginEmail) ?? throw new UserNotFoundException('Bad credentials'));
        } catch (UnknownMember) {
            throw new UserNotFoundException('Bad credentials');
        }
    }
}
