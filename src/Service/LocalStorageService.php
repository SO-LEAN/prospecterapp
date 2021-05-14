<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Stream;

class LocalStorageService
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $url;

    public function __construct(string $path, string $url)
    {
        $this->path = $path;
        $this->url = $url;
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

    /**
     * @param $url
     */
    public function removeFromUrl($url): void
    {
        $this->remove($this->urlToLocalPath($url));
    }

    /**
     * @param $url
     */
    public function getFromUrl($url): Stream
    {
        return new Stream($this->urlToLocalPath($url));
    }

    /**
     * @param $url
     */
    public function getPathFromUrl($url): string
    {
        return $this->urlToLocalPath($url);
    }

    public function getUrl(File $file): string
    {
        $length = strlen($this->path);

        if (substr($file->getRealPath(), 0, $length) !== $this->path) {
            throw new RuntimeException(sprintf('File %s cannot be reached', $file->getRealPath()));
        }

        return sprintf('%s/%s', $this->url, substr_replace($file->getRealPath(), '', 0, $length + 1));
    }

    private function applyPathStrategy(string $fileName): string
    {
        return sprintf('%d/%d/%s/', date('Y'), date('m'), $fileName);
    }

    /**
     * @param $url
     */
    private function urlToLocalPath($url): string
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
