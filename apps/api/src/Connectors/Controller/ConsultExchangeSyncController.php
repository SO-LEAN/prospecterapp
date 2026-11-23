<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\ExchangeSyncReader;
use App\Connectors\Resource\ExchangeSyncResource;
use App\Exchanges\ExchangeVoter;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ConsultExchangeSyncController
{
    public function __construct(
        private ExchangeSyncReader $exchangeSyncReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/google-sync', name: 'api_exchanges_google_sync', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Get(summary: 'Get the state of an exchange\'s push into my Google calendar: pending, synced or failed. There is none while the exchange is not pushed into my calendar')]
    #[OA\Response(response: 200, description: 'Its push state', content: new Model(type: ExchangeSyncResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponses->of($this->exchangeSyncReader->read($user->memberId(), $exchange->id));
    }
}
