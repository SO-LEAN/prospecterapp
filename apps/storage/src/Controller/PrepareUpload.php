<?php

namespace App\Controller;

use App\InternalCaller;
use App\PrivateFiles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PrepareUpload
{
    public function __construct(private InternalCaller $caller, private PrivateFiles $privateFiles)
    {
    }

    #[Route('/uploads', name: 'prepare_upload', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $this->caller->ensure($request);

        return new JsonResponse($this->privateFiles->prepareUpload(), Response::HTTP_CREATED);
    }
}
