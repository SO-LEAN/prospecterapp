<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContact;

/**
 * Erases a prospected person: the organizations that still name them ignore them from then on (ADR 0041).
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
