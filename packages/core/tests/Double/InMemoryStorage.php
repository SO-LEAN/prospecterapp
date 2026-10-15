<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Gateway\Storage;

/**
 * Keeps the stored files by URL. Like the real storage, adding a file moves it away from where it
 * was uploaded: reading its size or extension afterwards fails.
 */
final class InMemoryStorage implements Storage
{
    public const string BASE_URL = 'https://storage.test/';

    /** @var array<string, string> URL => original file name */
    private array $files = [];

    public function add(\SplFileInfo $file): string
    {
        $url = self::BASE_URL.$file->getFilename();
        $this->files[$url] = $file->getFilename();
        unlink($file->getPathname());

        return $url;
    }

    public function remove(string $url): void
    {
        unset($this->files[$url]);
    }

    public function has(string $url): bool
    {
        return isset($this->files[$url]);
    }

    public static function urlOf(string $fileName): string
    {
        return self::BASE_URL.$fileName;
    }
}
