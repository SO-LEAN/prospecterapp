<?php

namespace App\Shared\Resource;

/**
 * An image and its resized copies: `smallUrl` (96 px wide: avatars, markers, cards), `mediumUrl` (256 px:
 * a section showing the image); never wider than the original.
 */
final readonly class ImageResource
{
    public function __construct(
        public string $url,
        public string $smallUrl,
        public string $mediumUrl,
    ) {
    }
}
