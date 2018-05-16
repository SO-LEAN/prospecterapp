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

    /**
     * @param string $path
     * @param string $url
     */
    public function __construct(string $path, string $url)
    {
        $this->path = $path;
        $this->url = $url;
    }

    /**
     * @param File $file
     *
     * @return File
     */
    public function add(File $file): File
    {
        $fileName = md5(uniqid());

        return $file->move(sprintf('%s/%s', $this->path, $this->applyPathStrategy($fileName)), sprintf('%s.%s', $fileName, $file->guessExtension()));
    }

    /**
     * @param string $relativePath
     *
     * @return Stream
     */
    public function get(string $relativePath): Stream
    {
        return new Stream(sprintf('%s/%s', $this->path, $relativePath), true);
    }

    /**
     * @param string $relativePath
     */
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
     *
     * @return Stream
     */
    public function getFromUrl($url): Stream
    {
        return new Stream($this->urlToLocalPath($url));
    }

    /**
     * @param File $file
     *
     * @return string
     */
    public function getUrl(File $file): string
    {
        $length = strlen($this->path);

        if (substr($file->getRealPath(), 0, $length) !== $this->path) {
            throw new RuntimeException(sprintf('File %s cannot be reached', $file->getRealPath()));
        }

        return sprintf('%s/%s', $this->url, substr_replace($file->getRealPath(), '', 0, $length + 1));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function applyPathStrategy(string $fileName): string
    {
        return sprintf('%d/%d/%s/', date('Y'), date('m'), $fileName);
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function urlToLocalPath($url): string
    {
        return substr_replace($url, $this->path, 0, strlen($this->url));
    }
}
