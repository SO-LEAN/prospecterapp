<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

/**
 * Where to read a file joined to an exchange, for a moment only.
 */
final readonly class AttachmentDownloadResponse
{
    public function __construct(
        public string $name,
        public string $url,
    ) {
    }
}
