<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_show')]
final readonly class ShowOrganizationTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private OrganizationReader $organizationReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-organization', description: 'An organization the account of the member prospects: its identity, address, contact details, contacts, observations, and the links to what is read of it.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);

        return $this->results->of($this->organizationReader->resourceOf($organization));
    }
}
