<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\Accounts\Domain\Event\MemberRegistered;
use Prospecter\Core\Accounts\Domain\Event\RemindersChanged;
use Prospecter\Core\Accounts\Domain\Exception\AdministratorsOnly;
use Prospecter\Core\Accounts\Domain\Exception\MemberWithoutRole;
use Prospecter\Core\Accounts\Domain\Rule\AdministrationRight;
use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Aggregate root: a person who works in an account, with their roles. Their personal data live in PersonalData,
 * under their person id (ADR 0031). Authentication handles signing in with a login email (ADR 0032).
 */
final class Member implements ReleasesEvents
{
    use RecordsEvents;

    /** @var list<Role> */
    private readonly array $roles;
    private Reminders $reminders;

    private function __construct(
        private readonly MemberId $id,
        private readonly AccountId $accountId,
        private readonly ProfileId $personId,
        Role ...$roles,
    ) {
        $this->roles = array_values($roles);
        $this->reminders = Reminders::byDefault();
    }

    public static function register(MemberId $id, AccountId $accountId, ProfileId $personId, Role ...$roles): self
    {
        return new self($id, $accountId, $personId, ...$roles);
    }

    /**
     * An administrator registers a colleague in their account. The colleague gets a profile of their own and the
     * roles they work with. Every member has the right to sign in.
     *
     * @throws AdministratorsOnly
     * @throws MemberWithoutRole
     */
    public static function registerBy(self $administrator, Newcomer $newcomer, \DateTimeImmutable $at): self
    {
        $administrator->ensureAdministrator();
        self::ensureAWorkingRoleAmong($newcomer->roles);

        $member = new self($newcomer->id, $administrator->accountId, $newcomer->profile, ...self::signingInWith($newcomer->roles));
        $member->recordThat(new MemberRegistered($administrator->accountId, $administrator->id, $newcomer->id, $newcomer->profile, $at));

        return $member;
    }

    /**
     * @param list<Role> $roles
     */
    private static function ensureAWorkingRoleAmong(array $roles): void
    {
        if ([] === array_diff(array_map(static fn (Role $role): string => $role->value, $roles), [Role::User->value])) {
            throw MemberWithoutRole::because('A member needs at least one role: prospector, personal data or administrator', 'roles');
        }
    }

    /**
     * @param list<Role> $roles
     *
     * @return list<Role> the given roles and the right to sign in, each once, in the order of `Role`'s cases
     */
    private static function signingInWith(array $roles): array
    {
        return array_values(array_filter(Role::cases(), static fn (Role $role): bool => Role::User === $role || in_array($role, $roles, true)));
    }

    /**
     * The member chooses whether they are reminded of their planned exchanges, and how long before.
     */
    public function changeReminders(Reminders $reminders, \DateTimeImmutable $at): void
    {
        $this->reminders = $reminders;
        $this->recordThat(new RemindersChanged($this->accountId, $this->id, $at));
    }

    public function id(): MemberId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    private function isAdministrator(): bool
    {
        return AdministrationRight::isGrantedBy(...$this->roles);
    }

    private function ensureAdministrator(): void
    {
        if (!$this->isAdministrator()) {
            throw AdministratorsOnly::because('Only the account\'s administrators can register a member');
        }
    }
}
