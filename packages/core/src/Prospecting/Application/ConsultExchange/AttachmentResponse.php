<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\Attachment;

/**
 * A file joined to an exchange, its size in bytes; read by `DownloadAttachment`.
 */
final readonly class AttachmentResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public int $size,
        public string $mediaType,
    ) {
    }

    public static function of(Attachment $attachment): self
    {
        return new self($attachment->id->value, $attachment->name, $attachment->size, $attachment->mediaType);
    }
}
