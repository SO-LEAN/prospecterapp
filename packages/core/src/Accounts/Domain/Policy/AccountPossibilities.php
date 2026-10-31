<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Policy;

use Prospecter\Core\Accounts\Domain\Model\AccountIntent;

/**
 * What a member may change of an account (ADR 0088): everything if they belong to it, nothing otherwise; the
 * account refuses the rest, the reading of my account tells it.
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
