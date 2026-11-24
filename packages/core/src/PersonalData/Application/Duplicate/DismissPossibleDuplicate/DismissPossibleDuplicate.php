<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Duplicate\DismissPossibleDuplicate;

/**
 * Says that a member checked the contact, and that they are not a duplicate of the contact they resemble. Neither
 * contact is suspected of being the other's duplicate again.
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
