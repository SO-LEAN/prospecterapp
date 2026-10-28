<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersonsHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    public function __invoke(ConsultPersons $query, ConsultPersonsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $responses = [];

        foreach ($this->persons->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->ids)) as $person) {
            $response = $this->reachable($person, $reader);

            if (null !== $response) {
                $responses[$response->id] = $response;
            }
        }

        $output->present($responses);
    }

    private function reachable(Person $person, Reader $reader): ?PersonResponse
    {
        try {
            return PersonResponse::of($person, $reader);
        } catch (UnknownPerson) {
            return null;
        }
    }
}
