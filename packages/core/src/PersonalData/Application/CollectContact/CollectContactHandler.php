<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectContact;

use Prospecter\Core\PersonalData\Domain\EmailUniqueness;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class CollectContactHandler implements CommandHandler
{
    private EmailUniqueness $emails;

    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
        $this->emails = new EmailUniqueness($persons);
    }

    public function __invoke(CollectContact $command): void
    {
        $contact = Person::collectAsContact(
            by: $this->readers->get(new MemberId($command->readerId)),
            id: new PersonId($command->id),
            name: new PersonName($command->firstName, $command->lastName),
            jobTitle: $command->jobTitle,
            contactDetails: new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language),
            at: $command->executedAt,
        );
        $this->emails->ensureAvailableFor($contact);
        $this->persons->save($contact);
    }
}
