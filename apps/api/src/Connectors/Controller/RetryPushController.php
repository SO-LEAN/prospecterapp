<?php

namespace App\Connectors\Controller;

use App\Connectors\ExchangeSyncReader;
use App\Connectors\Resource\ExchangeSyncResource;
use App\Exchanges\ExchangeVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\RetryPush\RetryPush;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RetryPushController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ExchangeSyncReader $exchangeSyncReader,
    ) {
    }

    #[Route('/api/exchanges/{id}/google-sync/retries', name: 'api_exchanges_google_sync_retry', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Post(summary: 'Push an exchange into my calendar at Google again, as it is now; by its holder')]
    #[OA\Response(response: 200, description: 'Where its push stands now', content: new Model(type: ExchangeSyncResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the holder of the exchange', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RetryPush($user->memberId(), $exchange->id, $this->clock->now()));

        return $this->json($this->exchangeSyncReader->read($user->memberId(), $exchange->id));
    }
}
