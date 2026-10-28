<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangeJobTitle;

use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ChangeJobTitleHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ChangeJobTitle $command): void
    {
        $person = $this->persons->get(new PersonId($command->id));
        $person->changeJobTitle($command->jobTitle, by: $this->readers->readerFrom(new MemberId($command->readerId)), at: $command->executedAt);
        $this->persons->save($person);
        $this->publishWhatHappenedTo($person);
    }
}
