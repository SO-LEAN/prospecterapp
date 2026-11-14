<?php

namespace App\Connectors\Controller;

use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\Disconnect\Disconnect;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class DisconnectGoogleController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyConnectionsReader $myConnectionsReader,
    ) {
    }

    #[Route('/api/me/connections/google', name: 'api_me_connections_google_disconnect', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Disconnect my Google account: the access is revoked at Google, then forgotten')]
    #[OA\Response(response: 200, description: 'My connections, Google no longer among them', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No connection to Google', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new Disconnect($user->memberId(), self::GOOGLE, $this->clock->now()));

        return $this->json($this->myConnectionsReader->read($user->memberId()));
    }
}
