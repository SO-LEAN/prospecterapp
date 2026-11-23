<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\UntickToDo\UntickToDo;

/**
 * `DELETE /api/to-dos/{id}/done`, for an AI (ADR 0163). As for every removal, the AI asks its member before it calls
 * this tool.
 */
#[Adapts('api_to_dos_untick')]
final readonly class UntickToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string $id the to-do's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'reopen-todo', description: 'Untick a to-do ticked off by mistake. It is to do again, and the moment it was done is forgotten. Needs the right to manage personal data. Returns the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): UntickToDo => new UntickToDo($memberId, $toDo->id, $now),
        );
    }
}
