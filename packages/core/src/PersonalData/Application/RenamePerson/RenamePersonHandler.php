<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RenamePerson;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Service\DuplicateSuspicion;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class RenamePersonHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Readers $readers,
        private Events $events,
        private DuplicateSuspicion $duplicateSuspicion,
    ) {
    }

    public function __invoke(RenamePerson $command): void
    {
        $person = $this->persons->get(new PersonId($command->id));
        $reader = $this->readers->readerFrom(new MemberId($command->readerId));
        $person->rename(new PersonName($command->firstName, $command->lastName), by: $reader, at: $command->executedAt);
        $person->reconsiderDuplicates($this->duplicateSuspicion->resemblingWhereTheyWork($person), by: $reader->id, at: $command->executedAt);
        $this->persons->save($person);
        $this->publishWhatHappenedTo($person);
    }
}
