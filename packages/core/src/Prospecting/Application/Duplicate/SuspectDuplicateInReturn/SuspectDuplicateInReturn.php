<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Duplicate\SuspectDuplicateInReturn;

final readonly class SuspectDuplicateInReturn
{
    /**
     * @param string $organizationId the organization that suspects in return
     * @param string $duplicateId    the organization it suspects of being its duplicate
     */
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
