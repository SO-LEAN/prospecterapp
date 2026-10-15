<?php

namespace App\Shared;

use App\Service\ImageService;
use App\Shared\Resource\ImageResource;

/**
 * Builds image links: the original file and a resized copy generated on demand by the static host.
 */
/**
 * An image in the sizes the screens need, each a signed URL resized on first request by the static host
 * (`Controller\Statics\StorageController`): a client cannot ask for a size of its own.
 */
final readonly class ImageResourceFactory
{
    private const int SMALL_WIDTH = 96;
    private const int MEDIUM_WIDTH = 256;

    public function __construct(private ImageService $imageService)
    {
    }

    public function create(?string $url): ?ImageResource
    {
        if (null === $url || '' === $url) {
            return null;
        }

        return new ImageResource($url, $this->resized($url, self::SMALL_WIDTH), $this->resized($url, self::MEDIUM_WIDTH));
    }

    private function resized(string $url, int $width): string
    {
        try {
            return $this->imageService->buildOperationUrl($url, ImageService::OPERATION_RESIZING, [$width, 0]);
        } catch (\RuntimeException) {
            return $url;
        }
    }
}
