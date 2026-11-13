<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\UntickToDo;

use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class UntickToDoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(UntickToDo $command): void
    {
        $toDo = $this->toDos->get(new ToDoId($command->id));
        $toDo->untick(by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
