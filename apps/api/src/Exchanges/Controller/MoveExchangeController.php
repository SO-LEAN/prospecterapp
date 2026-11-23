<?php

declare(strict_types=1);

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\MomentInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MoveExchange\MoveExchange;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class MoveExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/moment', name: 'api_exchanges_move', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Patch(summary: 'Move a planned exchange to another moment in the future. It keeps its duration unless a new one is given. Only its holder can move it')]
    #[OA\Response(response: 200, description: 'The moved exchange. It links a move back while the moment it was moved from is still in the future', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the exchange\'s holder', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The exchange is no longer planned', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A missing moment, a moment in the past, or a duration out of range', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] MomentInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new MoveExchange(prospectorId: $user->memberId(), id: $exchange->id, plannedAt: $input->occurredAt ?? throw new \LogicException('The moment is validated'), executedAt: $this->clock->now(), durationInMinutes: $input->durationInMinutes));

        return $this->jsonResponses->of($this->exchangeReader->read($user->memberId(), $exchange->id, movedFrom: $exchange->occurredAt));
    }
}
