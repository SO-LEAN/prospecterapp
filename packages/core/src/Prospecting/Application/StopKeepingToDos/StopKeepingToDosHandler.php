<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingToDos;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An organization that is no longer prospected takes its to-dos along, as it does its exchanges. Those still kept for
 * another organization are saved. Each of the others is erased by its own command (EraseToDoLeftWithoutOrganization),
 * because one transaction holds a bounded number of writes.
 */
final readonly class StopKeepingToDosHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(StopKeepingToDos $command): void
    {
        $organization = new OrganizationId($command->organizationId);

        foreach ($this->toDos->keptFor($organization) as $toDo) {
            $toDo->stopKeepingFor($organization, by: new MemberId($command->memberId), at: $command->executedAt);

            if ($toDo->isKeptForAnOrganization()) {
                $this->toDos->save($toDo);
            }

            $this->publishWhatHappenedTo($toDo);
        }
    }
}
