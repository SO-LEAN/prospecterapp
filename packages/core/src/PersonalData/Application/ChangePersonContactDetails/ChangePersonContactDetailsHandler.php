<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonContactDetails;

use Prospecter\Core\PersonalData\Domain\EmailUniqueness;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class ChangePersonContactDetailsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private ReaderService $readerService,
        private Events $events,
        private EmailUniqueness $emails,
    ) {
    }

    public function __invoke(ChangePersonContactDetails $command): void
    {
        $person = $this->persons->get(new PersonId($command->id));
        $person->changeContactDetails(new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language), by: $this->readerService->readerFrom(new MemberId($command->readerId)), at: $command->executedAt);
        $this->emails->ensureAvailableFor($person);
        $this->persons->save($person);
        $this->publishWhatHappenedTo($person);
    }
}
