<?php

declare(strict_types=1);

namespace App\Tags\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\Tags\TagsReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}/tags`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_tags')]
final readonly class ShowOrganizationTagsTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private TagsReader $tagsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-organization-tags', description: 'The tags of an organization, which its contacts inherit.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);

        return $this->results->of($this->tagsReader->read($this->access->member()->memberId(), $organization->id));
    }
}
