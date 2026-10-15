<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member of an account as personal data sees them: who reads, for which account, which person they are,
 * and whether they hold the right to read personal data in clear.
 */
final readonly class Reader
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        public PersonId $personId,
        public bool $readsPersonalData,
    ) {
    }
}
