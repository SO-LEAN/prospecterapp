<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ReviseToDo;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ReviseToDoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(ReviseToDo $command): void
    {
        $toDo = $this->toDos->get(new ToDoId($command->id));
        $toDo->revise($command->text, new Due($command->dueOn, $command->dueAt), by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
