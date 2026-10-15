<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMember;

/**
 * The published language of a member (ADR 0045, 0047): only what the other contexts need, it changes with them,
 * never with the model of Accounts.
 */
final readonly class MemberResponse
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $personId,
        public bool $readsPersonalData,
    ) {
    }
}
