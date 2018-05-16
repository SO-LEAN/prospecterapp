<?php

namespace App\Controller\Statics;

use App\Service\ImageService;
use App\Service\LocalStorageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class StorageController extends Controller
{
    /**
     * @param Request $request
     *
     * @return BinaryFileResponse
     *
     * @Route("/{query}", name="storage_display", requirements={"query"=".+"})
     * @Cache(expires="+1 year", public=true, maxage="31536000", smaxage="31536000")
     */
    public function display(Request $request)
    {
        $imageService = $this->get(ImageService::class);
        $target = $imageService->buildUrlImageInfo($request->getUri());
        $parent = $target->getParent();

        $file = $imageService->applyOperation($target->getParent(), $target->getOperationName(), $target->getOperationArguments());

        /**
         * @var File
         */
        $parentFile = $this->get(LocalStorageService::class)->getFromUrl($parent->getTargetUrl());
        $file->move($parentFile->getPath(), $target->getTargetName());

        return new BinaryFileResponse(sprintf('%s/%s', $parentFile->getPath(), $target->getTargetName()));
    }
}
