<?php

namespace App\Controller;

use App\Files;
use App\InternalCaller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final readonly class RemoveFileController
{
    public function __construct(private InternalCaller $caller, private Files $files)
    {
    }

    #[Route('/files/{path}', name: 'remove_file', requirements: ['path' => Requirement::CATCH_ALL], methods: ['DELETE'])]
    public function __invoke(Request $request, string $path): Response
    {
        $this->caller->ensure($request);
        $this->files->remove($path);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
