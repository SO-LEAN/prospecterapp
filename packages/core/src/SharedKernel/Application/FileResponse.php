<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Domain\File;

/**
 * A stored file, as the use cases answer it.
 */
final readonly class FileResponse
{
    private function __construct(
        public string $url,
        public string $extension,
        public int $size,
    ) {
    }

    public static function orNone(?File $file): ?self
    {
        return null === $file ? null : new self($file->url, $file->extension, $file->size);
    }
}
