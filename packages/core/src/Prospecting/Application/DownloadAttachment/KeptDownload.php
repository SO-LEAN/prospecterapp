<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0084).
 */
final class KeptDownload implements DownloadAttachmentOutput
{
    public private(set) AttachmentDownloadResponse $download;

    public function present(AttachmentDownloadResponse $download): void
    {
        $this->download = $download;
    }
}
