<?php

namespace App\Gateway;

use App\Service\LocalStorageService;
use Prospecter\Core\Gateway\Storage;
use Symfony\Component\HttpFoundation\File\File;

class StorageLocalAdapter implements Storage
{
    public function __construct(private readonly LocalStorageService $localStorageService)
    {
    }

    public function add(\SplFileInfo $file): string
    {
        $stored = $this->localStorageService->add(new File($file->getPathname()));

        return $this->localStorageService->getUrl($stored);
    }

    public function remove(string $url): void
    {
        $this->localStorageService->removeFromUrl($url);
    }
}
