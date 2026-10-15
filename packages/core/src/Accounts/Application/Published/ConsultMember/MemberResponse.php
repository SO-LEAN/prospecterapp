<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Published\ConsultMember;

use Prospecter\Core\Accounts\Domain\Member;

/**
 * The published language of a member (ADR 0045): only what the other contexts need, it changes with them,
 * never with the model of Accounts.
 */
final readonly class MemberResponse
{
    private function __construct(
        public string $id,
        public string $accountId,
        public string $personId,
        public bool $readsPersonalData,
    ) {
    }

    public static function of(Member $member): self
    {
        return new self((string) $member->id(), (string) $member->accountId(), (string) $member->personId(), $member->readsPersonalData());
    }
}
