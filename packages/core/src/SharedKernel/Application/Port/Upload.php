<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

/**
 * A file the client sent to the storage, as the storage measured it.
 */
final readonly class Upload
{
    public function __construct(
        public int $size,
        public string $mediaType,
    ) {
    }
}
