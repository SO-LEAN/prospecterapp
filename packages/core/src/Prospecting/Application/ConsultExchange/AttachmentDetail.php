<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * A file joined to an exchange, its size in bytes; read by `DownloadAttachment`. To make conform, its contact
 * forgotten, it is removed.
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
