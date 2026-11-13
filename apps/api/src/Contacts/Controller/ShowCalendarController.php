<?php

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Input\PeriodQuery;
use App\Contacts\Resource\CalendarResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowCalendarController extends AbstractController
{
    public function __construct(private readonly AgendaReader $agendaReader)
    {
    }

    #[Route('/api/me/calendar', name: 'api_me_calendar', methods: ['GET'])]
    #[OA\Get(summary: 'The calendar of the signed-in member over a period: the exchanges they planned or noted, whatever their status')]
    #[OA\Response(response: 200, description: 'The exchanges of the period, by their moment', content: new Model(type: CalendarResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid period: a bound missing, an end before the start, longer than six weeks', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] PeriodQuery $period): JsonResponse
    {
        return $this->json($this->agendaReader->readCalendar($user->memberId(), $period->from, $period->to));
    }
}
