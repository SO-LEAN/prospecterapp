<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\DownloadAttachment;

/**
 * Tells where to read a file attached to an exchange. The address works for a moment only.
 */
final readonly class AttachmentDownloadResponse
{
    public function __construct(
        public string $name,
        public string $url,
    ) {
    }
}
