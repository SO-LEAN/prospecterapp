<?php

declare(strict_types=1);

namespace Tests\App\Double;

use App\Shared\Typed;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

/**
 * The storage app as the API tests see it: it keeps the files it is given, under a URL of the test storage, and
 * remembers the path each one was given from.
 */
final class InMemoryStorage implements Storage
{
    /** @var array<string, string> the path each file was given from, by its URL */
    private array $paths = [];

    public function add(\SplFileInfo $file): string
    {
        $url = sprintf('%s/%s.%s', Typed::string($_ENV['STORAGE_PUBLIC_URL']), bin2hex(random_bytes(8)), $file->getExtension());
        $this->paths[$url] = $file->getPathname();

        return $url;
    }

    public function remove(string $url): void
    {
        unset($this->paths[$url]);
    }

    public function has(string $url): bool
    {
        return isset($this->paths[$url]);
    }

    public function pathGivenFor(string $url): string
    {
        return $this->paths[$url] ?? throw new \OutOfBoundsException('No file stored at '.$url);
    }
}
