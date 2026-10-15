<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContact;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ForgetContactHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ForgetContact $command): void
    {
        $contact = $this->persons->get(new PersonId($command->id));
        $contact->ensureMayBeForgottenBy($this->readers->get(new MemberId($command->readerId)));
        $this->persons->remove($contact);
    }
}
