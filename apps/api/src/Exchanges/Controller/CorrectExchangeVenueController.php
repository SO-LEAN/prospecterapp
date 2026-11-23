<?php

declare(strict_types=1);

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\VenueCorrectionInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\CorrectExchangeVenue\CorrectExchangeVenue;
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
final readonly class CorrectExchangeVenueController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private ExchangeRevisions $revisions,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/exchanges/{id}/venue', name: 'api_exchanges_correct_venue', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Patch(summary: 'Correct a meeting\'s place or a video call\'s link, planned or done. Every prospector of the account can do it')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The corrected exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Neither a meeting nor a video call, or changed since the client read the revision it sent', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A link that is no web address, a place for a video call, a link for a meeting', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] VenueCorrectionInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new CorrectExchangeVenue(prospectorId: $user->memberId(), id: $exchange->id, place: $input->place, link: $input->link, executedAt: $this->clock->now()), $this->revisions->readAt($exchange->id, $revision));

        return $this->jsonResponses->of($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
