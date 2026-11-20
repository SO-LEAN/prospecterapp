<?php

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\EraseToDo\EraseToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * `DELETE /api/to-dos/{id}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_to_dos_erase')]
final readonly class EraseToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string $id the id of the to-do
     */
    #[McpTool(name: 'effacer-a-faire', description: 'Erase a to-do; needs the right to manage the personal data. Answers nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->dispatched(
            $id,
            null,
            static fn (null $input, ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): EraseToDo => new EraseToDo($memberId, $toDo->id, $now),
        );
    }
}
