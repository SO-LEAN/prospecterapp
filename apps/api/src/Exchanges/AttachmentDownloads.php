<?php

namespace App\Exchanges;

use App\Exchanges\Resource\AttachmentDownloadResource;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachment;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\KeptDownload;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * Where to read a file joined to an exchange, by a link signed for a moment (ADR 0077).
 */
final readonly class AttachmentDownloads
{
    public function __construct(private QueryHandler $downloadAttachment)
    {
    }

    public function of(string $memberId, string $exchangeId, string $attachmentId): AttachmentDownloadResource
    {
        $kept = new KeptDownload();
        ($this->downloadAttachment)(new DownloadAttachment($memberId, $exchangeId, $attachmentId), $kept);

        return new AttachmentDownloadResource($kept->download->name, $kept->download->url);
    }
}
