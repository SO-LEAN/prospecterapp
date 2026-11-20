<?php

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/to-dos`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_to_dos')]
final readonly class ShowToDosTool
{
    public function __construct(private ToolAccess $access, private ToDoReader $toDoReader, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-a-faire-contact', description: 'Les à-faire d\'un contact ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDoReader->ofContact($this->access->member()->memberId(), $id));
    }
}
