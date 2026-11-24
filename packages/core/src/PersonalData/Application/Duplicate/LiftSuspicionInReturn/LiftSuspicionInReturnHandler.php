<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Duplicate\LiftSuspicionInReturn;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact no longer resembles another one, or was forgotten. In return, the other contact is no longer suspected of
 * being their duplicate. When the other contact was forgotten meanwhile, the command has nothing left to do. When it
 * no longer suspects them, it records nothing.
 */
final readonly class LiftSuspicionInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(LiftSuspicionInReturn $command): void
    {
        try {
            $contact = $this->persons->get(new PersonId($command->contactId));
        } catch (UnknownPerson) {
            return;
        }

        $contact->liftSuspicionOf(new PersonId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
