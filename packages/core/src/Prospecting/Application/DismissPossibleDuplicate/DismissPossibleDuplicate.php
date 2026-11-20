<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate;

/**
 * Says, once checked, that the organization is no duplicate of the one it is suspected of being.
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
