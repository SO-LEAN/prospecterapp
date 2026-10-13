<?php

namespace App\Controller\Statics;

use App\Service\ImageService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class StorageController
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    #[Route('storage/{query}', name: 'storage_display', requirements: ['query' => Requirement::CATCH_ALL])]
    #[Cache(expires: '+1 year', maxage: 31536000, smaxage: 31536000, public: true)]
    public function display(Request $request): BinaryFileResponse
    {
        return new BinaryFileResponse($this->imageService->handleOperationUrl($request->getUri()));
    }
}
