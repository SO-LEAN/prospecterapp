<?php

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Input\ReaderClockQuery;
use App\Contacts\Resource\ExchangesToProcessResource;
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
final class ShowExchangesToProcessController extends AbstractController
{
    public function __construct(
        private readonly AgendaReader $agendaReader,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/me/exchanges-to-process', name: 'api_me_exchanges_to_process', methods: ['GET'])]
    #[OA\Get(summary: 'What the signed-in member has to process: the exchanges they planned whose end has passed, not yet marked done, missed nor cancelled, and their to-dos still to do of the day and before, on their clock, UTC unless their time zone is given')]
    #[OA\Response(response: 200, description: 'The exchanges to process, the oldest first, each with the links handling it, and the to-dos, the most overdue first', content: new Model(type: ExchangesToProcessResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A time zone unknown', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] ReaderClockQuery $clock): JsonResponse
    {
        return $this->json($this->agendaReader->readExchangesToProcess($user->memberId(), $clock->read($this->clock->now())));
    }
}
