<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoComposition;
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
        private ToDoComposition $toDos,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the organization's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-organization-todos', description: 'List the to-dos of an organization and of its contacts. Needs the right to manage personal data. Returns the to-dos.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDos->ofOrganization($this->access->member()->memberId(), $organization->id));
    }
}
