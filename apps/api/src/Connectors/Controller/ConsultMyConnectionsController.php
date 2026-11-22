<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
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
#[IsGranted('ROLE_USER')]
final class ConsultMyConnectionsController extends AbstractController
{
    public function __construct(
        private readonly MyConnectionsReader $myConnectionsReader,
    ) {
    }

    #[Route('/api/me/connections', name: 'api_me_connections', methods: ['GET'])]
    #[OA\Get(summary: 'Consult my connections to the services outside, in the state last seen, without asking them')]
    #[OA\Response(response: 200, description: 'My connections', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->myConnectionsReader->read($user->memberId()));
    }
}
