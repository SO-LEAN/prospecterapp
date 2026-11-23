<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

/**
 * A file that the client sent to the storage, as the storage measured it.
 */
final readonly class Upload
{
    public function __construct(
        public int $size,
        public string $mediaType,
    ) {
    }
}
