<?php

namespace App\Contacts\Mcp;

use App\Contacts\AgendaReader;
use App\Contacts\Input\SlotQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/planned-exchanges`, for an AI (ADR 0163).
 */
#[Adapts('api_me_planned_exchanges')]
final readonly class ShowOverlappingExchangesTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private AgendaReader $agendaReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $startsAt          when the slot starts, with its offset (2026-10-13T10:00:00+02:00)
     * @param ?int   $durationInMinutes how long it lasts, from 5 minutes to 8 hours; 30 when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-overlapping-exchanges', description: 'The planned exchanges of the agenda of the member a slot overlaps, before planning one.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $startsAt, ?int $durationInMinutes = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $slot = $this->inputs->mapped(SlotQuery::class, ['startsAt' => $startsAt, 'durationInMinutes' => $durationInMinutes]);
        \assert(null !== $slot->startsAt);

        return $this->results->of($this->agendaReader->readOverlapping($this->access->member()->memberId(), $slot->startsAt, $slot->durationInMinutes));
    }
}
