<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * A file attached to an exchange, with its size in bytes. `DownloadAttachment` reads it. Once the contact is
 * forgotten, the file must be redacted, and a member does so by removing it.
 */
final readonly class AttachmentDetail
{
    public function __construct(
        public string $id,
        public string $name,
        public int $size,
        public string $mediaType,
        public bool $toMakeConform,
    ) {
    }
}
