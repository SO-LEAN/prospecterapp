<?php

namespace App\Exchanges\Controller;

use App\Shared\Resource\UploadResource;
use App\Shared\UploadForms;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class PrepareUploadController extends AbstractController
{
    public function __construct(private readonly UploadForms $uploadForms)
    {
    }

    #[Route('/api/uploads', name: 'api_uploads_prepare', methods: ['POST'])]
    #[OA\Post(summary: 'A form the browser sends one private file with, itself, before joining it to an exchange')]
    #[OA\Response(response: 201, description: 'The form, signed for a few minutes', content: new Model(type: UploadResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector')]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->uploadForms->prepareUpload(), Response::HTTP_CREATED);
    }
}
