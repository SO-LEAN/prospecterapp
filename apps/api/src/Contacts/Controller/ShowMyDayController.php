<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Input\PeriodQuery;
use App\Contacts\Resource\MyDayResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowMyDayController extends AbstractController
{
    public function __construct(
        private readonly AgendaReader $agendaReader,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/me/day', name: 'api_me_day', methods: ['GET'])]
    #[OA\Get(summary: 'The day of the signed-in member: the exchanges they planned today, then the next five to come')]
    #[OA\Response(response: 200, description: 'The exchanges of the day still planned, then the next ones, the soonest first', content: new Model(type: MyDayResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid day: a bound missing, an end before the start', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] PeriodQuery $day): JsonResponse
    {
        return $this->json($this->agendaReader->readMyDay($user->memberId(), $day->start(), $day->end(), $this->clock->now()));
    }
}
