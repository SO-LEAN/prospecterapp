<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\NotTheAdministrator;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member as Connectors knows them from Accounts: their account, whether they administer it, and how long before
 * their planned exchanges they are reminded, none when they turned their reminders off.
 */
final readonly class Member
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        private bool $administers,
        public ?int $reminderMinutesBefore,
    ) {
    }

    /**
     * @throws NotTheAdministrator
     */
    public function administeredAccount(): AccountId
    {
        return $this->administers ? $this->accountId : throw NotTheAdministrator::ofTheClients();
    }
}
