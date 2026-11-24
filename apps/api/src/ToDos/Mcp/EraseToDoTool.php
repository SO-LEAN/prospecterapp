<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ToDo\EraseToDo\EraseToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * `DELETE /api/to-dos/{id}`, for an AI (ADR 0163). The AI asks its member before it calls this tool.
 */
#[Adapts('api_to_dos_erase')]
final readonly class EraseToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string $id the to-do's id
     */
    #[McpTool(name: 'delete-todo', description: 'Erase a to-do. Needs the right to manage personal data. Returns nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->changed(
            $id,
            static fn (ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): EraseToDo => new EraseToDo($memberId, $toDo->id, $now),
        );
    }
}
