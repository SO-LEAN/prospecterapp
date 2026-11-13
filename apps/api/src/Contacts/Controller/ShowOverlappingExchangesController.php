<?php

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Input\SlotQuery;
use App\Contacts\Resource\PlannedExchangesResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOverlappingExchangesController extends AbstractController
{
    public function __construct(
        private readonly AgendaReader $agendaReader,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/me/planned-exchanges', name: 'api_me_planned_exchanges', methods: ['GET'])]
    #[OA\Get(summary: 'The exchanges the signed-in member planned that overlap a slot, before planning another one in it')]
    #[OA\Response(response: 200, description: 'The exchanges overlapping the slot, the soonest first', content: new Model(type: PlannedExchangesResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid slot: no start, a duration out of 5 minutes to 8 hours', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] SlotQuery $slot = new SlotQuery()): JsonResponse
    {
        return $this->json($this->agendaReader->readOverlapping($user->memberId(), $slot->startsAt ?? $this->clock->now(), $slot->durationInMinutes));
    }
}
