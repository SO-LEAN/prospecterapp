<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The rules for an image sent to Prospecter, a logo or a picture. A member sends it from the screen, or an AI gives its
 * address.
 */
final class Images
{
    /** 2 MB, as the constraint counts them. */
    public const int LARGEST_BYTES = 2_000_000;

    public const array TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function constraint(): Assert\Image
    {
        return new Assert\Image(maxSize: self::LARGEST_BYTES, mimeTypes: self::TYPES);
    }
}
