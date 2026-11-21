<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\NotTheAdministrator;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member as Connectors knows them from Accounts: their account, whether they administer it, and how long before
 * their planned exchanges they are reminded, none when they turned their reminders off, and whether they manage the
 * personal data, which the import of their e-mails needs (ADR 0154); their first name as they present themselves,
 * which the guest they call reads.
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
     * The e-mails of a mailbox are personal data: only a member managing them catches them up (ADR 0081, 0154).
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayCatchUpMail(): void
    {
        if (!$this->mayCatchUpMail()) {
            throw PersonalDataManagersOnly::because('Only a member allowed to manage personal data catches up the e-mails of their mailbox');
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
