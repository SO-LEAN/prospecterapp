<?php

declare(strict_types=1);

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\CancellationInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\CancelExchange\CancelExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class CancelExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/cancellation', name: 'api_exchanges_cancel', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Post(summary: 'Cancel a planned exchange, and optionally say who called it off. The exchange stays in the timelines as cancelled. Only its holder can cancel it')]
    #[OA\Response(response: 200, description: 'The exchange, cancelled', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the exchange\'s holder', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The exchange is no longer planned', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Unknown canceller', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] CancellationInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CancelExchange(prospectorId: $user->memberId(), id: $exchange->id, canceller: $input->canceller, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
