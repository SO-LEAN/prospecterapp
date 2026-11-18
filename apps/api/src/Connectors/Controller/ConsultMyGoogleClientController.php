<?php

namespace App\Connectors\Controller;

use App\Connectors\GoogleClientReader;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final class ConsultMyGoogleClientController extends AbstractController
{
    public function __construct(
        private readonly GoogleClientReader $googleClientReader,
    ) {
    }

    #[Route('/api/me/organization/clients/google', name: 'api_me_google_client', methods: ['GET'])]
    #[OA\Get(summary: 'Consult the client of Google of my account, its secret never answered, and the redirect URI to declare in it')]
    #[OA\Response(response: 200, description: 'The client of Google of my account', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is no administrator of the account', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->googleClientReader->read($user));
    }
}
