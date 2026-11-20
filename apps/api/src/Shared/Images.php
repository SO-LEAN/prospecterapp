<?php

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * What an image sent to Prospecter is, a logo or a picture: sent from the screen or given by an AI by its address.
 */
final class Images
{
    /** 2 MB, as the constraint counts them */
    public const int LARGEST_BYTES = 2_000_000;

    public const array TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function constraint(): Assert\Image
    {
        return new Assert\Image(maxSize: self::LARGEST_BYTES, mimeTypes: self::TYPES);
    }
}
