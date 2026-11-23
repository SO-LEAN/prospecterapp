<?php

declare(strict_types=1);

namespace App\Security;

use App\Shared\Typed;
use Prospecter\Core\Accounts\Application\MyAccount;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The member that an access token names, as Symfony security sees them: their roles, and their ids for the commands
 * and queries.
 */
final readonly class User implements UserInterface
{
    /**
     * The role of a member who may manage personal data (ADR 0031).
     */
    public const string PERSONAL_DATA = 'ROLE_PERSONAL_DATA';

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

    public static function from(MyAccount $myAccount): self
    {
        return new self($myAccount->memberId, $myAccount->accountId, $myAccount->personId, $myAccount->roles);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return Typed::filledString($this->id);
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
