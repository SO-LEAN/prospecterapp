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
use App\ToDos\ToDoReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\NoteToDo\NoteToDo;
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
        private ToDoReader $toDoReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id    the id of the contact
     * @param string      $text  what is to do, 500 characters at most
     * @param string      $dueOn the day it is due, Y-m-d
     * @param string|null $dueAt the hour of that day, H:i, when said
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create-contact-todo', description: 'Note a to-do for a contact, held by the member, kept for the organizations the contact is part of; needs the right to manage the personal data. Answers the to-do.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
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

        return $this->results->of($this->toDoReader->read($memberId, $toDo));
    }
}
