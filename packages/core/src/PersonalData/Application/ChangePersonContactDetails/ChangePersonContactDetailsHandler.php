<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonContactDetails;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ChangePersonContactDetailsHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ChangePersonContactDetails $command): void
    {
        $person = $this->persons->get(new PersonId($command->id));
        $person->changeContactDetails(new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language), by: $this->readers->get(new MemberId($command->readerId)), at: $command->executedAt);
        $this->persons->save($person);
    }
}
