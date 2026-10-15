<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

final readonly class File
{
    public function __construct(
        public string $url,
        public string $extension,
        public int $size,
    ) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('URL "%s" is not valid', $url));
        }
    }
}
