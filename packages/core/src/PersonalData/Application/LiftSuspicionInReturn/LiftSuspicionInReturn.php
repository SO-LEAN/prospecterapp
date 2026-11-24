<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\LiftSuspicionInReturn;

final readonly class LiftSuspicionInReturn
{
    /**
     * @param string $contactId   the contact that lifts its suspicion in return
     * @param string $duplicateId the person it no longer suspects of being its duplicate
     */
    public function __construct(
        public string $memberId,
        public string $contactId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
