<?php

namespace App\Gateway;

use App\Service\LocalStorageService;
use Solean\CleanProspecter\Gateway\Storage;
use Symfony\Component\HttpFoundation\File\File;

class StorageLocalAdapter implements Storage
{
    public function __construct(private readonly LocalStorageService $localStorageService)
    {
    }

    public function add(\SplFileInfo $file): string
    {
        // Store a copy: use cases still read the original file (size, extension) after adding it
        $copy = tempnam(sys_get_temp_dir(), 'storage');
        copy($file->getPathname(), $copy);

        $stored = $this->localStorageService->add(new File($copy));

        return $this->localStorageService->getUrl($stored);
    }

    public function remove(string $url): void
    {
        $this->localStorageService->remove($url);
    }
}
