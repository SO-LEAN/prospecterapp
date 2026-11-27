<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\AgendaComposition;
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
        private AgendaComposition $agenda,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $startsAt          when the slot starts, with its offset (2026-10-13T10:00:00+02:00)
     * @param ?int   $durationInMinutes how long it lasts, from 5 minutes to 8 hours. It is 30 when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-overlapping-exchanges', description: 'List the planned exchanges in the member\'s agenda that overlap a slot, before you plan one in it.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $startsAt, ?int $durationInMinutes = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $slot = $this->inputs->mapped(SlotQuery::class, ['startsAt' => $startsAt, 'durationInMinutes' => $durationInMinutes]);
        \assert(null !== $slot->startsAt);

        return $this->results->of($this->agenda->readOverlapping($this->access->member()->memberId(), $slot->startsAt, $slot->durationInMinutes));
    }
}
