<?php

namespace App\Shared;

use App\Shared\Resource\ImageResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * An image in the sizes the screens need. The storage app serves the size of a file under
 * `<public url>/<size>/<path>` and makes it on first request, from a closed list of sizes (ADR 0033).
 */
final readonly class ImageResourceFactory
{
    public function __construct(#[Autowire(env: 'STORAGE_PUBLIC_URL')] private string $publicUrl)
    {
    }

    public function create(?string $url): ?ImageResource
    {
        if (null === $url || '' === $url) {
            return null;
        }

        return new ImageResource($url, $this->inSize('small', $url), $this->inSize('medium', $url));
    }

    private function inSize(string $size, string $url): string
    {
        if (!str_starts_with($url, $this->publicUrl.'/')) {
            return $url;
        }

        return sprintf('%s/%s/%s', $this->publicUrl, $size, substr($url, strlen($this->publicUrl) + 1));
    }
}
