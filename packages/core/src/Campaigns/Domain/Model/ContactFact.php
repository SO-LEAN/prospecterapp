<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact as PersonalData publishes it: whether the account keeps it, and how it is reached.
 */
final readonly class ContactFact implements Fact
{
    public function __construct(
        public AccountId $accountId,
        public PersonId $id,
        public int $revision,
        public bool $present,
        public Reach $reach,
    ) {
    }
}
