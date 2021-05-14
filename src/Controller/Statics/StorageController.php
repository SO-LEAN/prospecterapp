<?php

namespace App\Controller\Statics;

use App\Service\ImageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class StorageController
{
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * StorageController constructor.
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
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
