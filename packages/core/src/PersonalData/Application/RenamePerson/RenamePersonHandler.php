<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RenamePerson;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class RenamePersonHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(RenamePerson $command): void
    {
        $person = $this->persons->get($command->id);
        $person->rename($command->name, by: $this->readers->get($command->readerId));
        $this->persons->save($person);
    }
}
