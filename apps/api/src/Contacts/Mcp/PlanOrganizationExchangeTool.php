<?php

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationTimelineReader;
use App\Prospecting\Input\OrganizationPlannedExchangeInput;
use App\Prospecting\Mcp\OrganizationWrites;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\PlanOrganizationExchange\PlanOrganizationExchange;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/organizations/{id}/upcoming-exchanges`, for an AI (ADR 0163): pushed into the calendar of its member at
 * Google as one planned on the screen.
 */
#[Adapts('api_organizations_plan_exchange')]
final readonly class PlanOrganizationExchangeTool
{
    public function __construct(
        private OrganizationWrites $writes,
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private OrganizationTimelineReader $organizationTimelineReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id                the id of the organization
     * @param string      $channel           call, meeting or videoCall
     * @param string      $occurredAt        when it is to happen (ISO 8601), in the future
     * @param string|null $personId          the contact of the organization it is planned with; left out, with the organization itself
     * @param int|null    $durationInMinutes how long it lasts, from 5 minutes to 8 hours; 30 when left out
     * @param string|null $text              its agenda
     * @param string|null $place             where a meeting is held, written freely; a meeting only
     * @param string|null $link              the web address a video call is joined by; a video call only
     * @param bool        $linkRequested     a video call without a link asks a Google Meet link of the calendar of the member
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'schedule-organization-exchange', description: 'Plan a call, a meeting or a video call with an organization itself, or with one of its contacts, held by the member, and pushed into their calendar at Google when connected. Answers the exchanges to come with the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $channel, string $occurredAt, ?string $personId = null, ?int $durationInMinutes = null, ?string $text = null, ?string $place = null, ?string $link = null, bool $linkRequested = false): array
    {
        $organization = $this->writes->writable($id);
        $input = $this->inputs->mapped(OrganizationPlannedExchangeInput::class, [
            'exchange' => array_filter(['channel' => $channel, 'occurredAt' => $occurredAt, 'durationInMinutes' => $durationInMinutes, 'text' => $text, 'place' => $place, 'link' => $link, 'linkRequested' => $linkRequested], static fn (mixed $argument): bool => null !== $argument),
            'personId' => $personId,
        ]);
        $exchange = $input->exchange;
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new PlanOrganizationExchange(
            prospectorId: $memberId,
            id: $this->identities->next(),
            organizationId: $organization->id,
            personId: $input->personId,
            channel: $exchange->channel,
            plannedAt: $exchange->occurredAt ?? throw new \LogicException('A planned exchange validated has its moment'),
            durationInMinutes: $exchange->durationInMinutes,
            agenda: $exchange->text,
            place: $exchange->place,
            link: $exchange->link,
            linkRequested: $exchange->linkRequested,
            executedAt: $this->clock->now(),
            byAnAI: true,
        ));

        return $this->results->of($this->organizationTimelineReader->readUpcoming($memberId, $organization));
    }
}
