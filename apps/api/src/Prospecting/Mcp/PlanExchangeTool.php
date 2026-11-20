<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\PlannedExchangeInput;
use App\Prospecting\TimelineReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchange;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/upcoming-exchanges`, for an AI (ADR 0163): pushed into the calendar of its member at
 * Google as one planned on the screen.
 */
#[Adapts('api_contacts_plan_exchange')]
final readonly class PlanExchangeTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private TimelineReader $timelineReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id                the id of the contact
     * @param string      $channel           call, meeting or videoCall
     * @param string      $occurredAt        when it is to happen (ISO 8601), in the future
     * @param int|null    $durationInMinutes how long it lasts, from 5 minutes to 8 hours; 30 when left out
     * @param string|null $text              its agenda
     * @param string|null $place             where a meeting is held, written freely; a meeting only
     * @param string|null $link              the web address a video call is joined by; a video call only
     * @param bool        $linkRequested     a video call without a link asks a Google Meet link of the calendar of the member
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'planifier-echange', description: 'Plan a call, a meeting or a video call with a contact, held by the member, kept for the organizations the contact is part of, and pushed into their calendar at Google when connected. Answers the exchanges to come with the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $channel, string $occurredAt, ?int $durationInMinutes = null, ?string $text = null, ?string $place = null, ?string $link = null, bool $linkRequested = false): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->mapped(PlannedExchangeInput::class, ['channel' => $channel, 'occurredAt' => $occurredAt, 'durationInMinutes' => $durationInMinutes, 'text' => $text, 'place' => $place, 'link' => $link, 'linkRequested' => $linkRequested]);
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new PlanExchange(
            prospectorId: $memberId,
            id: $this->identities->next(),
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

        return $this->results->of($this->timelineReader->readUpcoming($memberId, $id));
    }
}
