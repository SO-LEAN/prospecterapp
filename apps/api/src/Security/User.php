<?php

namespace App\Security;

use Prospecter\Core\Accounts\Application\MemberResponse;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The member an access token names, as Symfony security sees it: its roles, and its identities for the
 * commands and queries.
 */
final readonly class User implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $id,
        private string $accountId,
        private string $personId,
        private array $roles,
    ) {
    }

    public static function from(MemberResponse $member): self
    {
        return new self($member->id, $member->accountId, $member->personId, $member->roles);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function memberId(): string
    {
        return $this->id;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function personId(): string
    {
        return $this->personId;
    }
}
