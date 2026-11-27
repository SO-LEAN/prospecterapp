<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoComposition;
use App\ToDos\ToDoResolver;
use App\ToDos\ToDoVoter;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/to-dos/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_to_dos_show')]
final readonly class ShowToDoTool
{
    public function __construct(
        private ToolAccess $access,
        private ToDoResolver $toDos,
        private ToDoComposition $toDoComposition,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the to-do's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-todo', description: 'Get a to-do. Needs the right to manage personal data. Returns its text, its due date, its holder, its contact or organization, and whether it is done.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $toDo = $this->toDos->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ToDoVoter::ACCESS, $toDo);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDoComposition->read($this->access->member()->memberId(), $toDo->id));
    }
}
