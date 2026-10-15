<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonContactDetails;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ChangePersonContactDetailsHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ChangePersonContactDetails $command): void
    {
        $person = $this->persons->get($command->id);
        $person->changeContactDetails($command->contactDetails, by: $this->readers->get($command->readerId));
        $this->persons->save($person);
    }
}
