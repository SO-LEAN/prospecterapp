<?php

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoReader;
use App\ToDos\ToDoResolver;
use App\ToDos\ToDoVoter;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing a to-do does as its controller (ADR 0163): the to-do of the route read, the member refused
 * unless a prospector with access to it and the right to manage the personal data, then the input of the endpoint
 * checked, its command dispatched, the to-do answered as the screen then reads it.
 */
final readonly class ToDoWrites
{
    public function __construct(
        private ToolAccess $access,
        private ToDoResolver $toDos,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ToDoReader $toDoReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput|null                                                           $input   the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, ToDoInList, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the to-do, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, ?object $input, \Closure $command): array
    {
        $toDo = $this->dispatched($id, $input, $command);

        return $this->results->of($this->toDoReader->read($this->access->member()->memberId(), $toDo->id));
    }

    /**
     * The command dispatched, nothing answered.
     *
     * @template TInput of object
     *
     * @param TInput|null                                                           $input
     * @param \Closure(TInput|null, ToDoInList, string, \DateTimeImmutable): object $command
     */
    public function dispatched(string $id, ?object $input, \Closure $command): ToDoInList
    {
        $toDo = $this->toDos->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ToDoVoter::ACCESS, $toDo);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $toDo, $this->access->member()->memberId(), $this->clock->now()));

        return $toDo;
    }
}
