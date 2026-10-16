<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Aggregate root: a person working in an account, with their roles. Their personal data live in PersonalData,
 * under their person id (ADR 0031); signing in, with a login email, is the business of authentication (ADR 0032).
 */
final readonly class Member
{
    /** @var list<Role> */
    private array $roles;

    private function __construct(
        private MemberId $id,
        private AccountId $accountId,
        private PersonId $personId,
        Role ...$roles,
    ) {
        $this->roles = array_values($roles);
    }

    public static function register(MemberId $id, AccountId $accountId, PersonId $personId, Role ...$roles): self
    {
        return new self($id, $accountId, $personId, ...$roles);
    }

    public function id(): MemberId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function personId(): PersonId
    {
        return $this->personId;
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function readsPersonalData(): bool
    {
        return in_array(Role::PersonalData, $this->roles, true) || in_array(Role::Admin, $this->roles, true);
    }
}
