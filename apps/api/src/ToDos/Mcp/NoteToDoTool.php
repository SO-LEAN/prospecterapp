<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\ToDos\Input\ToDoInput;
use App\ToDos\ToDoComposition;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ToDo\NoteToDo\NoteToDo;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/to-dos`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_note_to_do')]
final readonly class NoteToDoTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private ToDoComposition $toDos,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id    the contact's id
     * @param string      $text  what there is to do, 500 characters at most
     * @param string      $dueOn the day it is due, Y-m-d
     * @param string|null $dueAt the hour of that day, H:i, when given
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create-contact-todo', description: 'Log a to-do for a contact. The member holds it, and it is kept for the organizations the contact is part of. Needs the right to manage personal data. Returns the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $text, string $dueOn, ?string $dueAt = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->valid(new ToDoInput($text, $dueOn, $dueAt));
        $memberId = $this->access->member()->memberId();
        $toDo = $this->identities->next();
        $this->commandBus->dispatch(new NoteToDo(
            prospectorId: $memberId,
            id: $toDo,
            personId: $id,
            text: (string) $input->text,
            dueOn: (string) $input->dueOn,
            dueAt: $input->dueAt,
            executedAt: $this->clock->now(),
        ));

        return $this->results->of($this->toDos->read($memberId, $toDo));
    }
}
