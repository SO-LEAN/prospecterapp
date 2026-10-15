<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMember;

/**
 * Published to the other contexts (ADR 0045): who a member is, for the context that needs to know who acts.
 */
final readonly class ConsultMember
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
