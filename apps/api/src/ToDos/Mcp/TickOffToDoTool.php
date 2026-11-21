<?php

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\TickOffToDo\TickOffToDo;
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
     * @param string $id the id of the to-do
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'complete-todo', description: 'Tick a to-do off, done now; needs the right to manage the personal data. Answers the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): TickOffToDo => new TickOffToDo($memberId, $toDo->id, $now),
        );
    }
}
