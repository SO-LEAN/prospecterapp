<?php

namespace App\Controller;

use App\InternalCaller;
use App\PrivateFiles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShowUploadController
{
    public function __construct(private InternalCaller $caller, private PrivateFiles $privateFiles)
    {
    }

    #[Route('/uploads/{upload}', name: 'show_upload', requirements: ['upload' => PrivateFiles::NAME], methods: ['GET'])]
    public function __invoke(Request $request, string $upload): JsonResponse
    {
        $this->caller->ensure($request);
        $uploaded = $this->privateFiles->uploaded($upload);

        return new JsonResponse(['size' => $uploaded->size, 'mediaType' => $uploaded->mediaType]);
    }
}
