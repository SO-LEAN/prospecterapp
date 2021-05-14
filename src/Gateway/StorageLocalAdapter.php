<?php

namespace App\Gateway;

use App\Service\LocalStorageService;
use Solean\CleanProspecter\Gateway\Storage;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

class StorageLocalAdapter implements Storage
{
    /**
     * @var LocalStorageService
     */
    private $localStorageService;

    public function __construct(LocalStorageService $localStorageService)
    {
        $this->localStorageService = $localStorageService;
    }

    public function add(SplFileInfo $file): string
    {
        $file = $this->localStorageService->add(new File($file->getPathName()));

        return $this->localStorageService->getUrl($file);
    }

    public function remove(string $url)
    {
        $this->localStorageService->remove($url);
    }
}
