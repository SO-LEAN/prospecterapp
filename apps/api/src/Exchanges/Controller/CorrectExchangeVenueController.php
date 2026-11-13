<?php

namespace App\Exchanges\Controller;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeVoter;
use App\Exchanges\Input\VenueCorrectionInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\CorrectExchangeVenue\CorrectExchangeVenue;
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
final class CorrectExchangeVenueController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ExchangeReader $exchangeReader,
    ) {
    }

    #[Route('/api/exchanges/{id}/venue', name: 'api_exchanges_correct_venue', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[OA\Patch(summary: 'Correct where a meeting is held, or the link of a video call, planned or done; open to every prospector of the account')]
    #[OA\Response(response: 200, description: 'The corrected exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Neither a meeting nor a video call', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A link that is no web address, a place for a video call, a link for a meeting', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetail $exchange, #[MapRequestPayload] VenueCorrectionInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CorrectExchangeVenue($user->memberId(), $exchange->id, $input->place, $input->link, $this->clock->now()));

        return $this->json($this->exchangeReader->read($user->memberId(), $exchange->id));
    }
}
