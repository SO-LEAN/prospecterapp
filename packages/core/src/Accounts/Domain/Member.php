<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Aggregate root: a person working in an account, with their roles. Their personal data live in PersonalData,
 * under their person id (ADR 0031); signing in is not its business (ADR 0011).
 */
class Member
{
    /** @var list<Role> */
    private readonly array $roles;

    private function __construct(
        private readonly MemberId $id,
        private readonly AccountId $accountId,
        private readonly PersonId $personId,
        private string $userName,
        Role ...$roles,
    ) {
        self::ensureUserNameIsGiven($userName);
        $this->roles = array_values($roles);
    }

    public static function register(MemberId $id, AccountId $accountId, PersonId $personId, string $userName, Role ...$roles): self
    {
        return new self($id, $accountId, $personId, $userName, ...$roles);
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

    public function userName(): string
    {
        return $this->userName;
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

    /**
     * @return list<MemberIntent>
     */
    public function possibleIntents(): array
    {
        return MemberIntent::cases();
    }

    public function changeUserName(string $userName): void
    {
        self::ensureUserNameIsGiven($userName);
        $this->userName = $userName;
    }

    private static function ensureUserNameIsGiven(string $userName): void
    {
        if ('' === trim($userName)) {
            throw new BusinessRuleViolation('A user name is required', 'userName');
        }
    }
}
