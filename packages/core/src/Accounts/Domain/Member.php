<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\Accounts\Domain\Event\MemberRegistered;
use Prospecter\Core\Accounts\Domain\Exception\AdministratorsOnly;
use Prospecter\Core\Accounts\Domain\Exception\MemberWithoutRole;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a person working in an account, with their roles. Their personal data live in PersonalData,
 * under their person id (ADR 0031); signing in, with a login email, is the business of authentication (ADR 0032).
 */
final class Member implements ReleasesEvents
{
    use RecordsEvents;

    /** @var list<Role> */
    private readonly array $roles;

    private function __construct(
        private readonly MemberId $id,
        private readonly AccountId $accountId,
        private readonly PersonId $personId,
        Role ...$roles,
    ) {
        $this->roles = array_values($roles);
    }

    public static function register(MemberId $id, AccountId $accountId, PersonId $personId, Role ...$roles): self
    {
        return new self($id, $accountId, $personId, ...$roles);
    }

    /**
     * An administrator registers a colleague in their account, under a profile of their own, with the roles they
     * work with; every member signs in.
     *
     * @throws AdministratorsOnly
     * @throws MemberWithoutRole
     */
    public static function registerBy(self $administrator, MemberId $id, PersonId $profile, \DateTimeImmutable $at, Role ...$roles): self
    {
        $administrator->ensureAdministrator();
        self::ensureAWorkingRoleAmong($roles);

        $member = new self($id, $administrator->accountId, $profile, ...self::signingInWith($roles));
        $member->recordThat(new MemberRegistered($administrator->accountId, $administrator->id, $id, $profile, $at));

        return $member;
    }

    /**
     * @param list<Role> $roles
     */
    private static function ensureAWorkingRoleAmong(array $roles): void
    {
        if ([] === array_diff(array_map(static fn (Role $role): string => $role->value, $roles), [Role::User->value])) {
            throw MemberWithoutRole::because('A member needs a role among prospector, personal data and administrator', 'roles');
        }
    }

    /**
     * @param list<Role> $roles
     *
     * @return list<Role> the roles given and the right to sign in, each once, in the order of the roles
     */
    private static function signingInWith(array $roles): array
    {
        return array_values(array_filter(Role::cases(), static fn (Role $role): bool => Role::User === $role || in_array($role, $roles, true)));
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
        return in_array(Role::PersonalData, $this->roles, true) || $this->isAdministrator();
    }

    private function isAdministrator(): bool
    {
        return in_array(Role::Admin, $this->roles, true);
    }

    private function ensureAdministrator(): void
    {
        if (!$this->isAdministrator()) {
            throw AdministratorsOnly::because('Only an administrator of the account may register a member');
        }
    }
}
