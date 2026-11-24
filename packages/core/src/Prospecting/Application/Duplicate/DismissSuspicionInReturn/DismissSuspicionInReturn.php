<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Duplicate\DismissSuspicionInReturn;

final readonly class DismissSuspicionInReturn
{
    /**
     * @param string $organizationId the organization that dismisses its suspicion in return
     * @param string $duplicateId    the organization it knows is no duplicate
     */
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
