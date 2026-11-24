<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\PlannedExchangeInput;
use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineReader;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Exchange\PlanExchange\PlanExchange;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class PlanExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private TimelineReader $timelineReader,
        private JsonResponses $jsonResponses,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/contacts/{id}/upcoming-exchanges', name: 'api_contacts_plan_exchange', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Plan a call, a meeting or a video call with the contact. The signed-in member holds it, and it is kept for the contact\'s organizations')]
    #[OA\Response(response: 201, description: 'The upcoming exchanges with the contact, including the new one', content: new Model(type: TimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one in another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid exchange: a channel that cannot be planned, a moment that is not in the future, a duration outside 5 minutes to 8 hours, a place for a channel other than a meeting, a link for a channel other than a video call, or a link that is not a web address', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] PlannedExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $this->identities->next();
        $this->commandBus->dispatch(new PlanExchange(
            prospectorId: $user->memberId(),
            id: $exchange,
            personId: $id,
            channel: $input->channel,
            plannedAt: $input->occurredAt ?? throw new \LogicException('A planned exchange validated has its moment'),
            durationInMinutes: $input->durationInMinutes,
            agenda: $input->text,
            place: $input->place,
            link: $input->link,
            linkRequested: $input->linkRequested,
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->timelineReader->readUpcoming($user->memberId(), $id), Response::HTTP_CREATED, ['Location' => $this->urlGenerator->generate('api_exchanges_show', ['id' => $exchange])]);
    }
}
