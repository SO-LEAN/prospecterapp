<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\TimelineReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/upcoming-exchanges`, for an AI (ADR 0163).
 */
final readonly class ShowUpcomingExchangesTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private TimelineReader $timelineReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id    the id of the contact
     * @param ?string $after the cursor of the next slice, read in the link `next` of the slice before
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-echanges-a-venir-contact', description: 'Les échanges planifiés avec un contact, les plus proches d\'abord, par tranches.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new TimelineQuery($after));

        return $this->results->of($this->timelineReader->readUpcoming($this->access->member()->memberId(), $id, $query->after));
    }
}
