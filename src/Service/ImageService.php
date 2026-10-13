<?php

namespace App\Service;

use App\Service\Image\CipherHandler;
use App\Service\Image\OperatorFactory;
use App\Service\Image\UrlImageInfo;
use Symfony\Component\HttpFoundation\File\File;

class ImageService
{
    public const OPERATION_RESIZING = 'resize';

    public function __construct(private readonly CipherHandler $cipherHandler, private readonly LocalStorageService $localStorageService, private readonly OperatorFactory $imagineFactory)
    {
    }

    public function buildOperationUrl(string $url, string $operation, array $args = []): string
    {
        $info = $this->buildUrlImageInfo($url);
        $info->setOperation(sprintf('%s:%s', $operation, implode(',', $args)));

        return $info->getTargetUrl();
    }

    public function handleOperationUrl(string $uri): string
    {
        $target = $this->buildUrlImageInfo($uri);
        $newFile = $this->applyOperation($target);

        $dirName = dirname($this->localStorageService->getPathFromUrl($target->getParentUrl()));

        return $newFile->move($dirName, $target->getTargetName());
    }

    public function applyOperation(UrlImageInfo $info): File
    {
        $parent = $info->getParent();
        $operator = $this->imagineFactory->createOperator($info->getOperationName(), 'Imagick');
        $file = $this->localStorageService->getFromUrl($parent->getTargetUrl());

        return $operator->execute($file, $info->getOperationArguments());
    }

    private function buildUrlImageInfo(string $url): UrlImageInfo
    {
        return new UrlImageInfo($url, $this->cipherHandler);
    }
}
