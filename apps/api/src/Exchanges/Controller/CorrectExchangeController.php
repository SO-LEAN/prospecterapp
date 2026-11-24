<?php

declare(strict_types=1);

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\CorrectionInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Exchange\CorrectExchange\CorrectExchange;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class CorrectExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private ExchangeRevisions $revisions,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/facts', name: 'api_exchanges_correct', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Patch(summary: 'Correct an exchange that took place: its channel, its direction, whether it was missed, its moment and its text. Its contact stays the same')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The corrected exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Declared redacted while there is nothing to redact, or changed since the client read the revision it sent', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid correction: unknown channel or direction, a missed exchange that is no call, meeting or video call, a moment in the future, another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] CorrectionInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new CorrectExchange(
            prospectorId: $user->memberId(),
            id: $exchange->id,
            channel: $input->channel,
            direction: $input->direction,
            occurredAt: $input->occurredAt ?? throw new \LogicException('The moment of a correction is validated'),
            text: $input->text,
            missed: $input->missed,
            executedAt: $this->clock->now(),
            madeConform: $input->redacted,
        ), $this->revisions->readAt($exchange->id, $revision));

        return $this->jsonResponses->of($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
