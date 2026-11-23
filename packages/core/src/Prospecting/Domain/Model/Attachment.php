<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\AttachmentTooHeavy;

/**
 * A file attached to an exchange: its name as it was sent, its size in bytes, its media type, and the storage's
 * private file that holds it (ADR 0077).
 */
final readonly class Attachment
{
    public const int HEAVIEST = 10 * 1024 * 1024;

    /**
     * @throws AttachmentTooHeavy
     */
    public function __construct(
        public AttachmentId $id,
        public string $name,
        public int $size,
        public string $mediaType,
        public string $file,
    ) {
        if ($size > self::HEAVIEST) {
            throw AttachmentTooHeavy::because('An attachment can weigh at most 10 MB', 'file', context: ['name' => $name]);
        }
    }
}
