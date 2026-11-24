<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactLeftUnattached;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases a contact that nobody attached in time (ADR 0146). The member who held the contact erases them, as this
 * member collected them. It does nothing when the person is already gone.
 */
final readonly class ForgetContactLeftUnattachedHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ForgetContactLeftUnattached $command): void
    {
        $holder = $this->readers->readerFrom(new MemberId($command->holderId));

        foreach ($this->persons->ofIds([new PersonId($command->contactId)]) as $contact) {
            $contact->forget(by: $holder, at: $command->executedAt);
            $this->persons->remove($contact);
            $this->publishWhatHappenedTo($contact);
        }
    }
}
