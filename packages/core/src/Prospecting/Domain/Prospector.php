<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member of an account as prospecting sees them: who acts, and for which account they prospect.
 */
final readonly class Prospector
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        public bool $managesPersonalData = false,
    ) {
    }
}
