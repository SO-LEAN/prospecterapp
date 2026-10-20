<?php

namespace App\Exchanges;

use App\Exchanges\Input\CorrectionInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\CorrectExchange\CorrectExchange as CorrectExchangeCommand;
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
final class CorrectExchange extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly ExchangeReader $exchanges,
    ) {
    }

    #[Route('/api/exchanges/{id}', name: 'api_exchanges_correct', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Correct the channel, the direction, whether a call was missed, the moment and the text of an exchange; its contact stays')]
    #[OA\Response(response: 200, description: 'The corrected exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid correction: unknown channel or direction, a missed exchange that is no call, a moment in the future, another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] CorrectionInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new CorrectExchangeCommand(
            prospectorId: $user->memberId(),
            id: $id,
            channel: $input->channel,
            direction: $input->direction,
            occurredAt: $input->occurredAt ?? throw new \LogicException('The moment of a correction is validated'),
            text: $input->text,
            missed: $input->missed,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->exchanges->read($user->memberId(), $id));
    }
}
