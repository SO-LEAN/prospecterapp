<?php

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationTimelineReader;
use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}/upcoming-exchanges`, for an AI (ADR 0163).
 */
final readonly class ShowOrganizationUpcomingExchangesTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private OrganizationResolver $organizations,
        private OrganizationTimelineReader $timelineReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id    the id of the organization
     * @param ?string $after the cursor of the next slice, read in the link `next` of the slice before
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-echanges-a-venir-organisation', description: 'Les échanges planifiés avec une organisation et ses contacts, les plus proches d\'abord, par tranches.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, ?string $after = null): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $query = $this->inputs->valid(new TimelineQuery($after));

        return $this->results->of($this->timelineReader->readUpcoming($this->access->member()->memberId(), $organization, $query->after));
    }
}
