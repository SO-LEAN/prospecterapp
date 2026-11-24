<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\RemoveContact;

/**
 * Detaches a contact from the organization. Their personal data stay in PersonalData.
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
