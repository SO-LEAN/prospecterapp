<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Exchanges\Resource\AttachmentDownloadResource;
use Prospecter\Core\Prospecting\Application\Attachment\DownloadAttachment\DownloadAttachment;
use Prospecter\Core\Prospecting\Application\Attachment\DownloadAttachment\KeptDownload;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Where to read a file attached to an exchange. The link is signed for a short time (ADR 0077).
 */
final readonly class AttachmentDownloads
{
    public function __construct(private QueryHandler $downloadAttachment)
    {
    }

    public function of(string $memberId, string $exchangeId, string $attachmentId): AttachmentDownloadResource
    {
        $kept = new KeptDownload();
        $this->downloadAttachment->__invoke(new DownloadAttachment($memberId, $exchangeId, $attachmentId), $kept);

        return new AttachmentDownloadResource($kept->download->name, $kept->download->url);
    }
}
