<?php

namespace App\Controller;

use App\InternalCaller;
use App\PrivateFiles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RemovePrivateFileController
{
    public function __construct(private InternalCaller $caller, private PrivateFiles $privateFiles)
    {
    }

    #[Route('/private-files/{file}', name: 'remove_private_file', requirements: ['file' => PrivateFiles::NAME], methods: ['DELETE'])]
    public function __invoke(Request $request, string $file): Response
    {
        $this->caller->ensure($request);
        $this->privateFiles->remove($file);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
