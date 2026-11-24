<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\ForgetContactsLeftWithoutOrganization;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact is never alone (ADR 0042). So the handler erases the persons that no organization names any more. The
 * member whose gesture left them alone erases them.
 */
final readonly class ForgetContactsLeftWithoutOrganizationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ForgetContactsLeftWithoutOrganization $command): void
    {
        $reader = $this->readers->readerFrom(new MemberId($command->readerId));
        $alone = $this->persons->ofIds(array_map(static fn (string $contact): PersonId => new PersonId($contact), $command->contactIds));

        foreach ($alone as $contact) {
            $contact->forget(by: $reader, at: $command->executedAt);
        }

        foreach ($alone as $contact) {
            $this->persons->remove($contact);
            $this->publishWhatHappenedTo($contact);
        }
    }
}
