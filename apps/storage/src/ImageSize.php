<?php

namespace App;

/**
 * The sizes an image is served in, a closed list: nobody can make the storage compute a size of their own.
 * The width in pixels, the height following the ratio.
 */
enum ImageSize: int
{
    case Small = 96;
    case Medium = 256;

    public static function named(string $name): ?self
    {
        foreach (self::cases() as $size) {
            if ($size->name() === $name) {
                return $size;
            }
        }

        return null;
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
