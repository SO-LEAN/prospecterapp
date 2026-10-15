<?php

namespace App\Security;

use App\Entity\User;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\Uuid;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * The member an access token names (its subject is the member id), as Symfony security sees it.
 *
 * @implements UserProviderInterface<User>
 */
final readonly class MemberProvider implements UserProviderInterface
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        if (!Uuid::isValid($identifier)) {
            throw new UserNotFoundException('Unknown member');
        }

        try {
            return User::from($this->members->get(new MemberId($identifier)));
        } catch (UnknownMember) {
            throw new UserNotFoundException('Unknown member');
        }
    }

    public function refreshUser(UserInterface $user): User
    {
        throw new UnsupportedUserException('The API is stateless');
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
