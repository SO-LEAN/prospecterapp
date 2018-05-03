<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Stream;

class LocalStorageService
{
    private $path;

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
        return $file->move(sprintf('%s/%s', $this->path, $this->applyPathStrategy($file)), sprintf('%s.%s', md5(uniqid()), $file->guessExtension()));
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
     * @return File
     */
    public function getFromUrl($url): File
    {
        return new File($this->urlToLocalPath($url));
    }

    /**
     * @param File $file
     *
     * @return string
     */
    public function getUrl(File $file)
    {
        $length = strlen($this->path);

        if (substr($file->getRealPath(), 0, $length) !== $this->path) {
            throw new RuntimeException(sprintf('File %s cannot be reached', $file->getRealPath()));
        }

        return sprintf('%s/%s', $this->url, substr_replace($file->getRealPath(), '', 0, $length + 1));
    }

    /**
     * @param File $file
     *
     * @return string
     */
    private function applyPathStrategy(File $file)
    {
        return sprintf('%d/%d', date('Y'), date('m'));
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function urlToLocalPath($url)
    {
        return sprintf('%s/%s', substr_replace($url, $this->path, 0, strlen($this->url)));
    }
}
