<?php

namespace App\Gateway;

use App\Service\LocalStorageService;
use Prospecter\Core\Application\Port\Storage;
use Symfony\Component\HttpFoundation\File\File;

final readonly class LocalStorage implements Storage
{
    public function __construct(private LocalStorageService $localStorageService)
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
