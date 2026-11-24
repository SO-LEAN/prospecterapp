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
use Prospecter\Core\Connectors\Application\Calendar\RetryFailedPushes\RetryFailedPushes;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final readonly class RetryFailedPushesController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyConnectionsReader $myConnectionsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/connections/google/retries', name: 'api_me_connections_google_retry_failed', methods: ['POST'])]
    #[OA\Post(summary: 'Push again into my Google calendar each of my exchanges that Google refused')]
    #[OA\Response(response: 200, description: 'My connections, with the current count of my upcoming exchanges that are synced and of those that failed', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RetryFailedPushes($user->memberId(), $this->clock->now()));

        return $this->jsonResponses->of($this->myConnectionsReader->read($user->memberId()));
    }
}
