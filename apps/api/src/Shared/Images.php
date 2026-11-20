<?php

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * What an image sent to Prospecter is, a logo or a picture: sent from the screen or given by an AI by its address.
 */
final class Images
{
    public const string LARGEST = '2M';

    public const array TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function constraint(): Assert\Image
    {
        return new Assert\Image(maxSize: self::LARGEST, mimeTypes: self::TYPES);
    }
}
