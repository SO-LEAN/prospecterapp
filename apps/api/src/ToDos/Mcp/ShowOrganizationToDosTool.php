<?php

namespace App\ToDos\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/organizations/{id}/to-dos`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_to_dos')]
final readonly class ShowOrganizationToDosTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private ToDoReader $toDoReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the organization
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-a-faire-organisation', description: 'Les à-faire d\'une organisation et de ses contacts ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDoReader->ofOrganization($this->access->member()->memberId(), $organization->id));
    }
}
