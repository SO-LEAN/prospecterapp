<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveContact;

/**
 * Detaches a contact from the organization; their personal data stay in PersonalData.
 */
final readonly class RemoveContact
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
