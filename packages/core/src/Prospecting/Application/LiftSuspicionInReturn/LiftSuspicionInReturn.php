<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\LiftSuspicionInReturn;

final readonly class LiftSuspicionInReturn
{
    /**
     * @param string $organizationId the organization that lifts its suspicion in return
     * @param string $duplicateId    the organization it no longer suspects of being its duplicate
     */
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
