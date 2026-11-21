<?php

namespace App\ToDos\Mcp;

use App\Shared\Mcp\Adapts;
use App\ToDos\Input\ToDoInput;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ReviseToDo\ReviseToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * `PATCH /api/to-dos/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_to_dos_revise')]
final readonly class ReviseToDoTool
{
    public function __construct(private ToDoWrites $writes)
    {
    }

    /**
     * @param string      $id    the id of the to-do
     * @param string      $text  what is to do, 500 characters at most
     * @param string      $dueOn the day it is due, Y-m-d
     * @param string|null $dueAt the hour of that day, H:i, when said; removed when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-todo', description: 'Change the text of a to-do and when it is due, both given; needs the right to manage the personal data. Answers the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $text, string $dueOn, ?string $dueAt = null): array
    {
        return $this->writes->write(
            $id,
            new ToDoInput($text, $dueOn, $dueAt),
            static fn (ToDoInput $input, ToDoInList $toDo, string $memberId, \DateTimeImmutable $now): ReviseToDo => new ReviseToDo(
                prospectorId: $memberId,
                id: $toDo->id,
                text: (string) $input->text,
                dueOn: (string) $input->dueOn,
                dueAt: $input->dueAt,
                executedAt: $now,
            ),
        );
    }
}
