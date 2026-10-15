<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\IdentifyMember;

/**
 * Who a member is for the delivery that authenticated them: their identities and roles.
 */
final readonly class IdentifyMember
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
