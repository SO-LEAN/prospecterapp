<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContact;

/**
 * Erases a prospected person. Every organization that names them lets them go. Their exchanges stay, to be redacted
 * (ADR 0042, 0079).
 */
final readonly class ForgetContact
{
    public function __construct(
        public string $readerId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
