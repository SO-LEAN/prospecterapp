<?php

namespace App\Controller;

use App\InternalCaller;
use App\PrivateFiles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class KeepUpload
{
    public function __construct(private InternalCaller $caller, private PrivateFiles $privateFiles)
    {
    }

    #[Route('/private-files/{upload}', name: 'keep_upload', requirements: ['upload' => PrivateFiles::NAME], methods: ['POST'])]
    public function __invoke(Request $request, string $upload): Response
    {
        $this->caller->ensure($request);
        $this->privateFiles->keep($upload);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
