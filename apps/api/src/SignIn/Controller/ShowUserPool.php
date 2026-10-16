<?php

namespace App\SignIn\Controller;

use App\SignIn\Resource\UserPoolResource;
use App\SignIn\UserPool;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public: the SPA asks it before anyone is signed in.
 */
#[OA\Tag('Sign-in')]
final class ShowUserPool extends AbstractController
{
    public function __construct(
        private readonly UserPool $pool,
        #[Autowire(env: 'COGNITO_PUBLIC_ENDPOINT')]
        private readonly string $publicEndpoint,
    ) {
    }

    #[Route('/api/user-pool', name: 'api_user_pool', methods: ['GET'])]
    #[OA\Get(summary: 'Where the members sign in: the Cognito user pool and the client of the SPA', security: [])]
    #[OA\Response(response: 200, description: 'The user pool', content: new Model(type: UserPoolResource::class))]
    public function __invoke(): JsonResponse
    {
        return $this->json(new UserPoolResource($this->pool->region, $this->pool->id(), $this->pool->clientId(), '' === $this->publicEndpoint ? null : $this->publicEndpoint));
    }
}
