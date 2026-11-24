<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\DownloadAttachment;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptDownload implements DownloadAttachmentOutput
{
    public private(set) AttachmentDownloadResponse $download;

    public function present(AttachmentDownloadResponse $download): void
    {
        $this->download = $download;
    }
}
