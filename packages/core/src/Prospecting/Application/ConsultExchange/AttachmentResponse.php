<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;

/**
 * A file joined to an exchange, its size in bytes; read by `DownloadAttachment`. To make conform, its contact
 * forgotten, it is removed.
 */
final readonly class AttachmentResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public int $size,
        public string $mediaType,
        public bool $toMakeConform,
    ) {
    }

    public static function of(Attachment $attachment, Exchange $exchange): self
    {
        return new self($attachment->id->value, $attachment->name, $attachment->size, $attachment->mediaType, $exchange->areAttachmentsToMakeConform());
    }
}
