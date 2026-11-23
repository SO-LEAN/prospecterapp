<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * A person that an account keeps as a prospecting contact.
 */
final readonly class ContactFact implements Fact
{
    public function __construct(
        public AccountId $accountId,
        public ContactId $id,
        public int $revision,
        public bool $present,
    ) {
    }

    public function contribution(): FiguresChange
    {
        return $this->present ? new FiguresChange(contacts: 1) : FiguresChange::none();
    }
}
