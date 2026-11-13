<?php

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\MomentInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MoveExchange\MoveExchange;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class MoveExchangeController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ExchangeReader $exchangeReader,
    ) {
    }

    #[Route('/api/exchanges/{id}/moment', name: 'api_exchanges_move', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Patch(summary: 'Move a planned exchange to another moment to come, its duration kept; by its holder')]
    #[OA\Response(response: 200, description: 'The exchange, moved', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or not the holder of the exchange', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'No longer to come', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A moment missing, or in the past', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] MomentInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new MoveExchange($user->memberId(), $exchange->id, $input->occurredAt ?? throw new \LogicException('The moment is validated'), $this->clock->now()));

        return $this->json($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
