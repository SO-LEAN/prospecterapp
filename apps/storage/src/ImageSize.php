<?php

declare(strict_types=1);

namespace App;

/**
 * The sizes an image is served in. The list is closed, so nobody can make the storage compute a size of their own. The
 * value is the width in pixels, and the height follows the ratio.
 */
enum ImageSize: int
{
    case Small = 96;
    case Medium = 256;

    public static function named(string $name): ?self
    {
        return array_find(self::cases(), static fn (self $size): bool => $size->name() === $name);
    }

    public function name(): string
    {
        return strtolower($this->name);
    }

    public function pathOf(string $originalPath): string
    {
        return sprintf('%s/%s', $this->name(), $originalPath);
    }
}
