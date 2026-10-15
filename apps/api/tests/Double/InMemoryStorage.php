<?php

namespace Tests\App\Double;

use Prospecter\Core\SharedKernel\Application\Port\Storage;

/**
 * The storage app as the API tests see it: it keeps the files it is given, under a URL of the test storage.
 */
final class InMemoryStorage implements Storage
{
    /** @var array<string, true> */
    private array $urls = [];

    public function add(\SplFileInfo $file): string
    {
        $url = sprintf('%s/%s.%s', $_ENV['STORAGE_PUBLIC_URL'], bin2hex(random_bytes(8)), $file->getExtension());
        $this->urls[$url] = true;

        return $url;
    }

    public function remove(string $url): void
    {
        unset($this->urls[$url]);
    }

    public function has(string $url): bool
    {
        return isset($this->urls[$url]);
    }
}
