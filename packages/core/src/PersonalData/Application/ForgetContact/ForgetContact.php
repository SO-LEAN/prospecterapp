<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContact;

/**
 * Erases a prospected person: every organization naming them lets them go, their exchanges staying to make conform (ADR 0042, 0079).
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
