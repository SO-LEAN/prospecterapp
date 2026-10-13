<?php

namespace App\Api;

use App\Api\Resource\ImageResource;
use App\Service\ImageService;

/**
 * Builds image links: the original file and a resized copy generated on demand by the static host.
 */
final readonly class ImageResourceFactory
{
    private const int THUMBNAIL_WIDTH = 256;

    public function __construct(private ImageService $imageService)
    {
    }

    public function create(?string $url): ?ImageResource
    {
        if (null === $url || '' === $url) {
            return null;
        }

        try {
            $thumbnailUrl = $this->imageService->buildOperationUrl($url, ImageService::OPERATION_RESIZING, [self::THUMBNAIL_WIDTH, 0]);
        } catch (\RuntimeException) {
            $thumbnailUrl = $url;
        }

        return new ImageResource($url, $thumbnailUrl);
    }
}
