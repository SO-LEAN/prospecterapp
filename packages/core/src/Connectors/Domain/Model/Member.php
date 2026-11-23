<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\NotTheAdministrator;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member as Connectors knows them from Accounts. It holds their account and whether they administer it. It holds
 * how long before their planned exchanges they are reminded, and none when they turned their reminders off. It says
 * whether they manage personal data, which the e-mail import needs (ADR 0154). It also holds the first name they
 * present themselves with. The guest they call reads that name.
 */
final readonly class Member
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        private bool $administers,
        public ?int $reminderMinutesBefore,
        public bool $managesPersonalData,
        public ?string $firstName = null,
    ) {
    }

    /**
     * A mailbox's e-mails are personal data. Only a member who manages personal data catches them up (ADR 0081, 0154).
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayCatchUpMail(): void
    {
        if (!$this->mayCatchUpMail()) {
            throw PersonalDataManagersOnly::because('Only a member allowed to manage personal data can reimport e-mails from their mailbox');
        }
    }

    public function mayCatchUpMail(): bool
    {
        return $this->managesPersonalData;
    }

    /**
     * @throws NotTheAdministrator
     */
    public function administeredAccount(): AccountId
    {
        return $this->administers ? $this->accountId : throw NotTheAdministrator::ofTheClients();
    }
}
