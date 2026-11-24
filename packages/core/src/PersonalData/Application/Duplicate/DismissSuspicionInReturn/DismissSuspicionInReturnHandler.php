<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Duplicate\DismissSuspicionInReturn;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member checked that a contact is not a duplicate of another one. In return, the other contact is not a duplicate
 * of the first one either. When the contact was forgotten meanwhile, the command has nothing left to do. When the
 * contact knows it already, it records nothing.
 */
final readonly class DismissSuspicionInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(DismissSuspicionInReturn $command): void
    {
        try {
            $contact = $this->persons->get(new PersonId($command->contactId));
        } catch (UnknownPerson) {
            return;
        }

        $contact->knowItIsNoDuplicateOf(new PersonId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
