<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Duplicate\DismissSuspicionInReturn;

final readonly class DismissSuspicionInReturn
{
    /**
     * @param string $contactId   the contact that dismisses its suspicion in return
     * @param string $duplicateId the person it knows is no duplicate
     */
    public function __construct(
        public string $memberId,
        public string $contactId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
