<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\DismissPossibleDuplicate;

/**
 * Says, once checked, that the contact is no duplicate of the one they resemble: neither is suspected of being the
 * other's again.
 */
final readonly class DismissPossibleDuplicate
{
    public function __construct(
        public string $readerId,
        public string $id,
        public string $duplicateId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
