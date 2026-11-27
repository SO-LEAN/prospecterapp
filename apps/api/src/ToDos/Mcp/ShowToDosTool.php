<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoComposition;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/to-dos`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_to_dos')]
final readonly class ShowToDosTool
{
    public function __construct(private ToolAccess $access, private ToDoComposition $toDos, private ToolResults $results)
    {
    }

    /**
     * @param string $id the contact's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-contact-todos', description: 'List a contact\'s to-dos. Needs the right to manage personal data. Returns the to-dos.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDos->ofContact($this->access->member()->memberId(), $id));
    }
}
