<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;

final readonly class ConsultPersonHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ConsultPerson $query): PersonResponse
    {
        return PersonResponse::of($this->persons->get($query->id), $this->readers->get($query->readerId));
    }
}
