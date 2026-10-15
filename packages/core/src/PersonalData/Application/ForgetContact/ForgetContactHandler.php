<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContact;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Erases the person; the event it records detaches it from its organizations (ContactForgotten).
 */
final readonly class ForgetContactHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ForgetContact $command): void
    {
        $contact = $this->persons->get(new PersonId($command->id));
        $contact->forget(by: $this->readers->get(new MemberId($command->readerId)), at: $command->executedAt);
        $this->persons->remove($contact);
        $this->events->publish(...$contact->releaseEvents());
    }
}
