<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Input\ReaderClockQuery;
use App\Contacts\Resource\ExchangesToProcessResource;
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
final readonly class ShowExchangesToProcessController
{
    public function __construct(
        private AgendaReader $agendaReader,
        private ClockInterface $clock,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/exchanges-to-process', name: 'api_me_exchanges_to_process', methods: ['GET'])]
    #[OA\Get(summary: 'Get what the signed-in member has to process. These are the exchanges they planned whose end has passed and that are not yet marked done, missed or cancelled. They also include the member\'s open to-dos due today or before, on the member\'s clock. That clock is UTC unless the request gives their time zone')]
    #[OA\Response(response: 200, description: 'The exchanges to process, the oldest first, each with the links that handle it. Then the to-dos, the most overdue first', content: new Model(type: ExchangesToProcessResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'An unknown time zone', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] ReaderClockQuery $clock): JsonResponse
    {
        return $this->jsonResponses->of($this->agendaReader->readExchangesToProcess($user->memberId(), $clock->read($this->clock->now())));
    }
}
