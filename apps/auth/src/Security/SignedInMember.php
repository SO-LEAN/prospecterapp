<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A member signed in on the authorization server. Its identifier, the subject of the access tokens, is its
 * member id: the API finds the member by it, and changing the login email does not change it.
 */
final readonly class SignedInMember implements UserInterface
{
    public function __construct(private string $memberId, private string $loginEmail)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->memberId;
    }

    public function loginEmail(): string
    {
        return $this->loginEmail;
    }

    public function getRoles(): array
    {
        return ['ROLE_MEMBER'];
    }
}
