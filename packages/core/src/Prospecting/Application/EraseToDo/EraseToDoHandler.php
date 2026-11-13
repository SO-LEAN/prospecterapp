<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDo;

use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class EraseToDoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(EraseToDo $command): void
    {
        $toDo = $this->toDos->get(new ToDoId($command->id));
        $toDo->erase(by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->toDos->remove($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
