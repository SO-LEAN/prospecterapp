<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\AgendaComposition;
use App\Contacts\Input\CalendarQuery;
use App\Contacts\Resource\CalendarResource;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowCalendarController
{
    public function __construct(
        private AgendaComposition $agenda,
        private ClockInterface $clock,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/calendar', name: 'api_me_calendar', methods: ['GET'])]
    #[OA\Get(summary: 'Get the signed-in member\'s calendar over a period. It holds the exchanges they planned or logged, whatever their status, and their to-dos due in the period, done or not. It says what is late on the member\'s clock. That clock uses the period\'s offset unless the request gives their time zone')]
    #[OA\Response(response: 200, description: 'The period\'s exchanges, by their moment', content: new Model(type: CalendarResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid period: a missing bound, an end before the start, or more than six weeks. Or an unknown time zone', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] CalendarQuery $period): JsonResponse
    {
        return $this->jsonResponses->of($this->agenda->readCalendar($user->memberId(), $period->start(), $period->end(), $period->read($this->clock->now())));
    }
}
