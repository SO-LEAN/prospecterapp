<?php

declare(strict_types=1);

namespace App\Shared\Resource;

/**
 * An image and its resized copies: `smallUrl` (96 px wide, for avatars, markers and cards) and `mediumUrl` (256 px, for
 * a section that shows the image). A copy is never wider than the original.
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
