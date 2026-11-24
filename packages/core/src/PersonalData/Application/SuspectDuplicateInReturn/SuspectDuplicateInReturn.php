<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SuspectDuplicateInReturn;

final readonly class SuspectDuplicateInReturn
{
    /**
     * @param string $contactId   the contact that suspects in return
     * @param string $duplicateId the person it suspects of being its duplicate
     */
    public function __construct(
        public string $memberId,
        public string $contactId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
