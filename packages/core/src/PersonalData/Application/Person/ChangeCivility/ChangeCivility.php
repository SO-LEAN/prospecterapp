<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\ChangeCivility;

/**
 * Logs a contact's title, which tells how to address them, or logs that it is unknown (x).
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
