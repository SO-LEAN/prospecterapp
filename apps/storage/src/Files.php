<?php

namespace App;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The stored files: an original under `<year>/<month>/<name>/<name>.<extension>`, its resized copies under
 * `<size>/` the same path. Nginx serves what exists; PHP only stores, removes and resizes.
 */
final readonly class Files
{
    public function __construct(private string $directory, private string $publicUrl)
    {
    }

    /**
     * @return string the public URL of the stored file
     */
    public function store(File $upload): string
    {
        $name = bin2hex(random_bytes(16));
        $relativePath = sprintf('%s/%s/%s.%s', date('Y/m'), $name, $name, $upload->guessExtension() ?? $upload->getExtension());
        $upload->move(dirname($this->absolute($relativePath)), basename($relativePath));

        return sprintf('%s/%s', $this->publicUrl, $relativePath);
    }

    /**
     * Removes the original and every copy made from it.
     */
    public function remove(string $relativePath): void
    {
        foreach ([$relativePath, ...array_map(static fn (ImageSize $size): string => $size->pathOf($relativePath), ImageSize::cases())] as $path) {
            if (is_file($this->absolute($path))) {
                unlink($this->absolute($path));
            }
        }
    }

    public function original(string $relativePath): string
    {
        $path = $this->absolute($relativePath);

        return is_file($path) ? $path : throw new NotFoundHttpException(sprintf('No file %s', $relativePath));
    }

    public function absolute(string $relativePath): string
    {
        if (str_contains($relativePath, '..')) {
            throw new NotFoundHttpException(sprintf('No file %s', $relativePath));
        }

        return sprintf('%s/%s', $this->directory, $relativePath);
    }
}
