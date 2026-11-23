<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Rule;

use Prospecter\Core\Accounts\Domain\Model\AccountIntent;

/**
 * What a member may change in an account (ADR 0088). A member of the account may change everything. Anyone else
 * may change nothing. The account refuses any other intent, and the member's view of their account lists these.
 */
final class AccountPossibilities
{
    /**
     * @return list<AccountIntent>
     */
    #[\NoDiscard]
    public static function given(bool $isAMember): array
    {
        return $isAMember ? AccountIntent::cases() : [];
    }
}
