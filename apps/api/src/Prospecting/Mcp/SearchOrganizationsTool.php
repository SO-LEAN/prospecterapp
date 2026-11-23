<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\OrganizationListQuery;
use App\Prospecting\OrganizationSearchReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_search')]
final readonly class SearchOrganizationsTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private OrganizationSearchReader $organizations,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $q     the start of the searched organizations' corporate name, or their email. All organizations when empty
     * @param ?string $after the cursor of the next slice, read in the previous slice's `next` link
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'search-organizations', description: 'Search the organizations that the member\'s account prospects, by the start of their corporate name or by their e-mail. Returns them in slices, the most recent first.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $q = '', ?string $after = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $query = $this->inputs->valid(new OrganizationListQuery($q, $after));

        return $this->results->of($this->organizations->search($this->access->member()->memberId(), $query->q, $query->after));
    }
}
