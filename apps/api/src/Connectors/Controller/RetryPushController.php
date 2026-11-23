<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\ExchangeSyncReader;
use App\Connectors\Resource\ExchangeSyncResource;
use App\Exchanges\ExchangeVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\RetryPush\RetryPush;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class RetryPushController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeSyncReader $exchangeSyncReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/google-sync/retries', name: 'api_exchanges_google_sync_retry', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Post(summary: 'Push an exchange again, as it is now, into my Google calendar. Only its holder can do it')]
    #[OA\Response(response: 200, description: 'Its current push state', content: new Model(type: ExchangeSyncResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the holder of the exchange', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RetryPush($user->memberId(), $exchange->id, $this->clock->now()));

        return $this->jsonResponses->of($this->exchangeSyncReader->read($user->memberId(), $exchange->id));
    }
}
