<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompleteContact;

use Prospecter\Core\PersonalData\Domain\Model\ContactSupplement;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Service\EmailUniqueness;
use Prospecter\Core\PersonalData\Domain\Service\ReferenceUniqueness;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class CompleteContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Readers $readers,
        private Events $events,
        private EmailUniqueness $emails,
        private ReferenceUniqueness $references,
    ) {
    }

    public function __invoke(CompleteContact $command): void
    {
        $contact = $this->persons->get(new PersonId($command->id));
        $heldBefore = $contact->referencesHeld();
        $contact->completeWith(ContactSupplement::given($command->civility, $command->jobTitle, $command->email, $command->phoneNumber, $command->links), by: $this->readers->readerFrom(new MemberId($command->readerId)), at: $command->executedAt);
        $this->emails->ensureAvailableFor($contact);
        $this->references->ensureAvailableFor($contact, $heldBefore);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
