<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SuspectDuplicateInReturn;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A newcomer resembles a contact. In return, that contact is suspected of being the newcomer's duplicate. When the
 * contact was forgotten meanwhile, the command has nothing left to do. When the contact suspects them already, it
 * records nothing.
 */
final readonly class SuspectDuplicateInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(SuspectDuplicateInReturn $command): void
    {
        try {
            $contact = $this->persons->get(new PersonId($command->contactId));
        } catch (UnknownPerson) {
            return;
        }

        $contact->suspectDuplicateOf(new PersonId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
