<?php

namespace App\Security;

use App\Credentials;
use App\LoginEmails;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SignedInMember>
 */
final readonly class MemberProvider implements UserProviderInterface
{
    public function __construct(private LoginEmails $loginEmails)
    {
    }

    public function loadUserByIdentifier(string $identifier): SignedInMember
    {
        return $this->signedIn($this->loginEmails->of($identifier));
    }

    /**
     * The member signing in with this login email.
     */
    public function byLoginEmail(string $loginEmail): SignedInMember
    {
        return $this->signedIn($this->loginEmails->credentialsOf($loginEmail));
    }

    public function refreshUser(UserInterface $user): SignedInMember
    {
        if (!$user instanceof SignedInMember) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SignedInMember::class === $class;
    }

    private function signedIn(?Credentials $credentials): SignedInMember
    {
        return null === $credentials ? throw new UserNotFoundException('Bad credentials') : new SignedInMember($credentials->memberId(), $credentials->email());
    }
}
