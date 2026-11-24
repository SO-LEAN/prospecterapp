<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ToDo\TickOffToDo\TickOffToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * `POST /api/to-dos/{id}/done`, for an AI (ADR 0163).
 */
#[Adapts('api_to_dos_tick_off')]
final readonly class TickOffToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string $id the to-do's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'complete-todo', description: 'Tick a to-do off, as done now. Needs the right to manage personal data. Returns the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): TickOffToDo => new TickOffToDo($memberId, $toDo->id, $now),
        );
    }
}
