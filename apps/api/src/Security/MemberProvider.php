<?php

declare(strict_types=1);

namespace App\Security;

use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\KeptAccount;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Loads the member that an access token names (its subject is the member id), as Symfony security sees them.
 *
 * @implements UserProviderInterface<User>
 */
final readonly class MemberProvider implements UserProviderInterface
{
    public function __construct(
        private QueryHandler $consultMyAccount,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        if (!Uuid::isValid($identifier)) {
            throw new UserNotFoundException('Unknown member');
        }

        $kept = new KeptAccount();

        try {
            $this->consultMyAccount->__invoke(new ConsultMyAccount($identifier), $kept);
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
