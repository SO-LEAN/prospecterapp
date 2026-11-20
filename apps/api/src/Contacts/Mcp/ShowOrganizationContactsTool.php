<?php

namespace App\Contacts\Mcp;

use App\Contacts\OrganizationContactsReader;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}/contacts`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_contacts')]
final readonly class ShowOrganizationContactsTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private OrganizationContactsReader $contacts,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-contacts-organisation', description: 'The contacts of an organization, in clear or masked as the member reads them.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);

        return $this->results->of($this->contacts->read($this->access->member()->memberId(), $organization));
    }
}
