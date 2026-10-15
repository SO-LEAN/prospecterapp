<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

/**
 * Attaches a person the account prospects, collected beforehand in PersonalData (ADR 0041).
 */
final readonly class AddContact
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
