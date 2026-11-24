<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\Connection\Disconnect\Disconnect;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final readonly class DisconnectGoogleController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyConnectionsReader $myConnectionsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/connections/google', name: 'api_me_connections_google_disconnect', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Disconnect my Google account. The access is revoked at Google, then forgotten')]
    #[OA\Response(response: 200, description: 'My connections, without Google', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No connection to Google', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new Disconnect($user->memberId(), self::GOOGLE, $this->clock->now()));

        return $this->jsonResponses->of($this->myConnectionsReader->read($user->memberId()));
    }
}
