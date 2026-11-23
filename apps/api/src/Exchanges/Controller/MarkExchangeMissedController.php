<?php

declare(strict_types=1);

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MarkExchangeMissed\MarkExchangeMissed;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class MarkExchangeMissedController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/missed', name: 'api_exchanges_mark_missed', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Post(summary: 'Mark a planned exchange missed once its time has come: the contact did not come or did not answer. Only its holder can mark it')]
    #[OA\Response(response: 200, description: 'The exchange, missed', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the exchange\'s holder', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The exchange is no longer planned, or its time has not come', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new MarkExchangeMissed($user->memberId(), $exchange->id, $this->clock->now()));

        return $this->jsonResponses->of($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
