<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate;

/**
 * States, after a check, that the organization is not a duplicate of the one it is suspected of duplicating.
 */
final readonly class DismissPossibleDuplicate
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
