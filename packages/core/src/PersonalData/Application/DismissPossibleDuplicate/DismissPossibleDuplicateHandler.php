<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\DismissPossibleDuplicate;

use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class DismissPossibleDuplicateHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Readers $readers,
        private Events $events,
    ) {
    }

    public function __invoke(DismissPossibleDuplicate $command): void
    {
        $contact = $this->persons->get(new PersonId($command->id));
        $contact->dismissSuspicionOf(new PersonId($command->duplicateId), by: $this->readers->readerFrom(new MemberId($command->readerId)), at: $command->executedAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
