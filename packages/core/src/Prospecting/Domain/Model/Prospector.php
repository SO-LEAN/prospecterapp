<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member of an account as prospecting sees them: who reads, for which account they prospect, and whether
 * they read the personal data an exchange may hold.
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
     * What is said with a contact is personal data: noting it needs the right to manage them.
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
