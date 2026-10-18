<?php

namespace App;

use Symfony\Component\HttpFoundation\File\File;

/**
 * The stored files: an original under `<year>/<month>/<name>/<name>.<extension>`, its resized copies under
 * `<size>/` the same key, all in the bucket. What exists is served without PHP; PHP stores, removes and resizes.
 */
final readonly class Files
{
    public function __construct(private Bucket $bucket, private PublicCache $publicCache, private string $publicUrl)
    {
    }

    /**
     * @return string the public URL of the stored file
     */
    public function store(File $upload): string
    {
        $name = bin2hex(random_bytes(16));
        $key = sprintf('%s/%s/%s.%s', date('Y/m'), $name, $name, $upload->guessExtension() ?? $upload->getExtension());
        $this->bucket->put($key, new StoredFile($upload->getContent(), $upload->getMimeType() ?? 'application/octet-stream'));

        return sprintf('%s/%s', $this->publicUrl, $key);
    }

    /**
     * Removes the original and every copy made from it, from the bucket and from what keeps copies in front of it.
     */
    public function remove(string $key): void
    {
        $keys = [$key, ...array_map(static fn (ImageSize $size): string => $size->pathOf($key), ImageSize::cases())];
        $this->bucket->delete($keys);
        $this->publicCache->forget($keys);
    }
}
