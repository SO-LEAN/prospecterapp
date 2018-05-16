<?php

namespace App\Service;

use App\Service\Image\UrlImageInfo;
use App\Service\Image\CipherHandler;
use App\Service\Image\OperatorFactory;
use Symfony\Component\HttpFoundation\File\File;

class ImageService
{
    /**
     * @var CipherHandler
     */
    private $cipherHandler;

    /**
     * @var LocalStorageService
     */
    private $localStorageService;

    /**
     * @var OperatorFactory
     */
    private $imagineFactory;

    /**
     * ImageService constructor.
     *
     * @param CipherHandler       $cipherHandler
     * @param LocalStorageService $localStorageService
     * @param OperatorFactory     $imagineFactory
     */
    public function __construct(CipherHandler $cipherHandler, LocalStorageService $localStorageService, OperatorFactory $imagineFactory)
    {
        $this->cipherHandler = $cipherHandler;
        $this->localStorageService = $localStorageService;
        $this->imagineFactory = $imagineFactory;
    }

    /**
     * @param string $url
     * @param string $operation
     * @param array  $args
     *
     * @return string
     */
    public function buildOperationUrl(string $url, string $operation, array $args = []): string
    {
        $info = $this->buildUrlImageInfo($url);
        $info->setOperation(sprintf('%s:%s', $operation, implode(',', $args)));

        return $info->getTargetUrl();
    }

    /**
     * @param string $url
     *
     * @return UrlImageInfo
     */
    public function buildUrlImageInfo(string $url): UrlImageInfo
    {
        return new UrlImageInfo($url, $this->cipherHandler);
    }

    /**
     * @param UrlImageInfo $fileInfo
     * @param string       $operation
     * @param mixed        ...$args
     *
     * @return File
     */
    public function applyOperation(UrlImageInfo $fileInfo, $operation, array $args): File
    {
        $operator = $this->imagineFactory->createOperator($operation, 'Imagick');
        $file = $this->localStorageService->getFromUrl($fileInfo->getTargetUrl());

        return $operator->execute($file, $args);
    }
}
