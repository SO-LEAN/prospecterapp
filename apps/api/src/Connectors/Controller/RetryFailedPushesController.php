<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\RetryFailedPushes\RetryFailedPushes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class RetryFailedPushesController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyConnectionsReader $myConnectionsReader,
    ) {
    }

    #[Route('/api/me/connections/google/retries', name: 'api_me_connections_google_retry_failed', methods: ['POST'])]
    #[OA\Post(summary: 'Push again into my calendar at Google every exchange of mine Google refused')]
    #[OA\Response(response: 200, description: 'My connections, counting my exchanges to come synced and failed now', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RetryFailedPushes($user->memberId(), $this->clock->now()));

        return $this->json($this->myConnectionsReader->read($user->memberId()));
    }
}
