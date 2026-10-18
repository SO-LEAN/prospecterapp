<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
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

    /**
     * @return list<TimelineIntent>
     */
    public function timelineIntents(): array
    {
        return $this->managesPersonalData ? TimelineIntent::cases() : [];
    }

    /**
     * What is said with a contact is personal data: noting or reading it needs the right to manage them.
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayExchangeWithTheContacts(): void
    {
        if (!$this->managesPersonalData) {
            throw PersonalDataManagersOnly::because('Only a prospector allowed to manage personal data may exchange with the contacts');
        }
    }
}
