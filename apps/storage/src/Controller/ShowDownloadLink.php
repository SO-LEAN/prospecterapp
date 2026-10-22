<?php

namespace App\Controller;

use App\InternalCaller;
use App\PrivateFiles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShowDownloadLink
{
    public function __construct(private InternalCaller $caller, private PrivateFiles $privateFiles)
    {
    }

    #[Route('/private-files/{file}/download-link', name: 'show_download_link', requirements: ['file' => PrivateFiles::NAME], methods: ['GET'])]
    public function __invoke(Request $request, string $file): JsonResponse
    {
        $this->caller->ensure($request);
        $name = $request->query->getString('name');

        if ('' === trim($name)) {
            throw new BadRequestHttpException('The name the file is downloaded under is expected in "name".');
        }

        return new JsonResponse(['url' => $this->privateFiles->downloadLinkOf($file, $name)]);
    }
}
