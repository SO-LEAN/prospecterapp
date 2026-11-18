<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangeCivility;

/**
 * Notes how one addresses a contact, or that it is unknown (x).
 */
final readonly class ChangeCivility
{
    public function __construct(
        public string $readerId,
        public string $id,
        public string $civility,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
