<?php

namespace App\Controller;

use App\Files;
use App\InternalCaller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class StoreFileController
{
    public function __construct(private InternalCaller $caller, private Files $files)
    {
    }

    #[Route('/files', name: 'store_file', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $this->caller->ensure($request);
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A file is expected in the "file" field.');
        }

        return new JsonResponse(['url' => $this->files->store($file)], Response::HTTP_CREATED);
    }
}
