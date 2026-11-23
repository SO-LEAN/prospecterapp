<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationTimelineReader;
use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}/exchanges-to-redact`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_exchanges_to_redact')]
final readonly class ShowExchangesToMakeConformTool
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
     * @param string  $id    the organization's id
     * @param ?string $after the next slice's cursor, read in the previous slice's link `next`
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-exchanges-pending-redaction', description: 'Lists an organization\'s exchanges to redact, the most recent first, by slices.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, ?string $after = null): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $query = $this->inputs->valid(new TimelineQuery($after));

        return $this->results->of($this->timelineReader->readToMakeConform($this->access->member()->memberId(), $organization, $query->after));
    }
}
