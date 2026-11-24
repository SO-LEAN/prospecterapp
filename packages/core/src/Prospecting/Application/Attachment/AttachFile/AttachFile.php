<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\AttachFile;

/**
 * Attaches a file to an exchange. The client sent the file to the storage as an upload, and the file keeps its name.
 * The addition does not run again when the exchange changed meanwhile. The storage keeps the upload before the
 * commit, and keeps it only once.
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
