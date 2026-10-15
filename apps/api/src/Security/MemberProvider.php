<?php

namespace App\Security;

use Prospecter\Core\Accounts\Application\IdentifyMember\IdentifyMember;
use Prospecter\Core\Accounts\Application\IdentifyMember\IdentifyMemberHandler;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The member an access token names (its subject is the member id), as Symfony security sees it.
 *
 * @implements UserProviderInterface<User>
 */
final readonly class MemberProvider implements UserProviderInterface
{
    private RefusalTranslatingHandler $identifyMember;

    public function __construct(IdentifyMemberHandler $identifyMember)
    {
        $this->identifyMember = new RefusalTranslatingHandler($identifyMember);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        if (!Uuid::isValid($identifier)) {
            throw new UserNotFoundException('Unknown member');
        }

        try {
            return User::from(($this->identifyMember)(new IdentifyMember($identifier)));
        } catch (UseCaseRefused $refusal) {
            throw ErrorType::NotFound === $refusal->type ? new UserNotFoundException('Unknown member', previous: $refusal) : $refusal;
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
