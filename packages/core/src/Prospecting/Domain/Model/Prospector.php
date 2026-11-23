<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An account's member as Prospecting sees them: who reads, which account they prospect for, and whether they read
 * the personal data an exchange may hold.
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
     * What a member and a contact say to each other is personal data. Logging it needs the right to manage personal
     * data.
     *
     * @throws PersonalDataManagersOnly
     */
    public function ensureMayExchangeWithTheContacts(): void
    {
        if (!$this->managesPersonalData) {
            throw PersonalDataManagersOnly::because('Only a prospector allowed to manage personal data can exchange with contacts');
        }
    }
}
