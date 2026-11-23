<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineReader;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowUpcomingExchangesController
{
    public function __construct(
        private TimelineReader $timelineReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/contacts/{id}/upcoming-exchanges', name: 'api_contacts_upcoming_exchanges', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'The upcoming exchanges with the contact, soonest first. The first one is the next exchange')]
    #[OA\Response(response: 200, description: 'A slice of the upcoming exchanges. The link next leads to the next slice', content: new Model(type: TimelineResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one in another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user, #[MapQueryString] TimelineQuery $query = new TimelineQuery()): JsonResponse
    {
        return $this->jsonResponses->of($this->timelineReader->readUpcoming($user->memberId(), $id, $query->after));
    }
}
