<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\UntickToDo\UntickToDo;

/**
 * `DELETE /api/to-dos/{id}/done`, for an AI (ADR 0163), which asks its member before calling it, as for every
 * removal.
 */
#[Adapts('api_to_dos_untick')]
final readonly class UntickToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string $id the id of the to-do
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'reopen-todo', description: 'Untick a to-do ticked off by mistake: it is to do again, the moment it was done forgotten; needs the right to manage the personal data. Answers the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): UntickToDo => new UntickToDo($memberId, $toDo->id, $now),
        );
    }
}
