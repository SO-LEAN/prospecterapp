<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\OrganizationTimelineComposition;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Input\OrganizationPlannedExchangeInput;
use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Exchange\PlanOrganizationExchange\PlanOrganizationExchange;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class PlanOrganizationExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private OrganizationTimelineComposition $organizationTimeline,
        private JsonResponses $jsonResponses,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/organizations/{id}/upcoming-exchanges', name: 'api_organizations_plan_exchange', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Post(summary: 'Plan a call, a meeting or a video call with the organization itself, or with one of its contacts, held by the signed-in member')]
    #[OA\Response(response: 201, description: 'The exchanges to come with the organization, the new one among them', content: new Model(type: OrganizationTimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The organization is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The person is not a contact of the organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid exchange: a channel that cannot be planned, a moment that is not in the future, a duration outside 5 minutes to 8 hours, a place for a channel other than a meeting, a link for a channel other than a video call, or a link that is not a web address', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] OrganizationPlannedExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $input->exchange;
        $planned = $this->identities->next();
        $this->commandBus->dispatch(new PlanOrganizationExchange(
            prospectorId: $user->memberId(),
            id: $planned,
            organizationId: $organization->id,
            personId: $input->personId,
            channel: $exchange->channel,
            plannedAt: $exchange->occurredAt ?? throw new \LogicException('A validated planned exchange has its moment'),
            durationInMinutes: $exchange->durationInMinutes,
            agenda: $exchange->text,
            place: $exchange->place,
            link: $exchange->link,
            linkRequested: $exchange->linkRequested,
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->organizationTimeline->readUpcoming($user->memberId(), $organization), Response::HTTP_CREATED, ['Location' => $this->urlGenerator->generate('api_exchanges_show', ['id' => $planned])]);
    }
}
