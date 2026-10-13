<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Stream;

class LocalStorageService
{
    public function __construct(private readonly string $path, private readonly string $url)
    {
    }

    public function add(File $file): File
    {
        $fileName = md5(uniqid());

        return $file->move(sprintf('%s/%s', $this->path, $this->applyPathStrategy($fileName)), sprintf('%s.%s', $fileName, $this->deduceExtension($file)));
    }

    public function get(string $relativePath): Stream
    {
        return new Stream(sprintf('%s/%s', $this->path, $relativePath), true);
    }

    public function remove(string $relativePath): void
    {
        unlink($relativePath);
    }

    public function removeFromUrl(string $url): void
    {
        $this->remove($this->urlToLocalPath($url));
    }

    public function getFromUrl(string $url): Stream
    {
        return new Stream($this->urlToLocalPath($url));
    }

    public function getPathFromUrl(string $url): string
    {
        return $this->urlToLocalPath($url);
    }

    public function getUrl(File $file): string
    {
        $length = strlen($this->path);

        if (substr($file->getRealPath(), 0, $length) !== $this->path) {
            throw new \RuntimeException(sprintf('File %s cannot be reached', $file->getRealPath()));
        }

        return sprintf('%s/%s', $this->url, substr_replace($file->getRealPath(), '', 0, $length + 1));
    }

    private function applyPathStrategy(string $fileName): string
    {
        return sprintf('%d/%d/%s/', date('Y'), date('m'), $fileName);
    }

    private function urlToLocalPath(string $url): string
    {
        return substr_replace($url, $this->path, 0, strlen($this->url));
    }

    private function deduceExtension(File $file): string
    {
        $extension = $file->guessExtension();

        if (null === $extension) {
            if ('image/svg+xml' === mime_content_type($file->getPathname())) {
                $extension = 'svg';
            }
        }

        return $extension;
    }
}
