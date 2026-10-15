<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersonHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ConsultPerson $query): PersonResponse
    {
        return PersonResponse::of($this->persons->get(new PersonId($query->id)), $this->readers->get(new MemberId($query->readerId)));
    }
}
