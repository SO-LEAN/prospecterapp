<?php

namespace App\Security;

use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\KeptAccount;
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
    public function __construct(
        private RefusalTranslatingHandler $consultMyAccount,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        if (!Uuid::isValid($identifier)) {
            throw new UserNotFoundException('Unknown member');
        }

        $kept = new KeptAccount();

        try {
            ($this->consultMyAccount)(new ConsultMyAccount($identifier), $kept);
        } catch (UseCaseRefused $refusal) {
            throw ErrorType::NotFound === $refusal->type ? new UserNotFoundException('Unknown member', previous: $refusal) : $refusal;
        }

        return User::from($kept->account);
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
