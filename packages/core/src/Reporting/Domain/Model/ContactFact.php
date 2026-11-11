<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person an account keeps as a contact of its prospecting.
 */
final readonly class ContactFact implements Fact
{
    public function __construct(
        public AccountId $accountId,
        public PersonId $id,
        public int $revision,
        public bool $present,
    ) {
    }

    public function contribution(): FiguresChange
    {
        return $this->present ? new FiguresChange(contacts: 1) : FiguresChange::none();
    }
}
