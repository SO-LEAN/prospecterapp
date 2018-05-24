<?php

namespace App\Controller\Statics;

use App\Service\ImageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class StorageController
{
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * StorageController constructor.
     *
     * @param ImageService $imageService
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @param Request $request
     *
     * @return BinaryFileResponse
     *
     * @Route("storage/{query}", name="storage_display", requirements={"query"=".+"})
     * @Cache(expires="+1 year", public=true, maxage="31536000", smaxage="31536000")
     */
    public function display(Request $request)
    {
        return new BinaryFileResponse($this->imageService->handleOperationUrl($request->getUri()));
    }
}
