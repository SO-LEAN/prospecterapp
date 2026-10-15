<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\Role;

/**
 * A member as the delivery that authenticated them needs it.
 */
final readonly class MemberResponse
{
    /**
     * @param list<string> $roles values of the roles: ROLE_USER, ROLE_PROSPECTOR...
     */
    private function __construct(
        public string $id,
        public string $accountId,
        public string $personId,
        public array $roles,
    ) {
    }

    public static function of(Member $member): self
    {
        return new self(
            $member->id()->value,
            $member->accountId()->value,
            $member->personId()->value,
            array_map(static fn (Role $role): string => $role->value, $member->roles()),
        );
    }
}
