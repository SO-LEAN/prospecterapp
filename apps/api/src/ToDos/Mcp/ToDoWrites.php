<?php

declare(strict_types=1);

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
     * @param TInput                                                           $input   the input of the endpoint
     * @param \Closure(TInput, ToDoInList, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the to-do, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, object $input, \Closure $command): array
    {
        return $this->answer($this->dispatched($id, $input, $command));
    }

    /**
     * It does the same as write() for an endpoint without an input.
     *
     * @param \Closure(ToDoInList, string, \DateTimeImmutable): object $command the command of the endpoint, for the to-do, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function change(string $id, \Closure $command): array
    {
        return $this->answer($this->changed($id, $command));
    }

    /**
     * The command dispatched, nothing answered.
     *
     * @template TInput of object
     *
     * @param TInput                                                           $input
     * @param \Closure(TInput, ToDoInList, string, \DateTimeImmutable): object $command
     */
    public function dispatched(string $id, object $input, \Closure $command): ToDoInList
    {
        $toDo = $this->writable($id);
        $this->commandBus->dispatch($command($this->inputs->valid($input), $toDo, $this->access->member()->memberId(), $this->clock->now()));

        return $toDo;
    }

    /**
     * It does the same as dispatched() for an endpoint without an input.
     *
     * @param \Closure(ToDoInList, string, \DateTimeImmutable): object $command the command of the endpoint, for the to-do, by the member, at this moment
     */
    public function changed(string $id, \Closure $command): ToDoInList
    {
        $toDo = $this->writable($id);
        $this->commandBus->dispatch($command($toDo, $this->access->member()->memberId(), $this->clock->now()));

        return $toDo;
    }

    /**
     * @return array<string, mixed>
     */
    private function answer(ToDoInList $toDo): array
    {
        return $this->results->of($this->toDoReader->read($this->access->member()->memberId(), $toDo->id));
    }

    /**
     * Reads the route's to-do. It refuses the member unless they are a prospector with access to it and the right to
     * manage the personal data.
     */
    private function writable(string $id): ToDoInList
    {
        $toDo = $this->toDos->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ToDoVoter::ACCESS, $toDo);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $toDo;
    }
}
