<?php

namespace App\Api\Resource;

final readonly class ImageResource
{
    public function __construct(
        public string $url,
        public string $thumbnailUrl,
    ) {
    }
}
