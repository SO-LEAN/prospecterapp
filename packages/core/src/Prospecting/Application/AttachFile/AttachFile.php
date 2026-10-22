<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachFile;

/**
 * Joins to an exchange a file the client sent to the storage as an upload, under the name it had.
 */
final readonly class AttachFile
{
    public function __construct(
        public string $prospectorId,
        public string $exchangeId,
        public string $id,
        public string $upload,
        public string $name,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
