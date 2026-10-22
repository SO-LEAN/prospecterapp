<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\AttachmentTooHeavy;

/**
 * A file joined to an exchange: its name as it was sent, its size in bytes, its media type, and the private file
 * of the storage holding it (ADR 0077).
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
            throw AttachmentTooHeavy::because('An attachment weighs 10 MB at most', 'file', context: ['name' => $name]);
        }
    }
}
