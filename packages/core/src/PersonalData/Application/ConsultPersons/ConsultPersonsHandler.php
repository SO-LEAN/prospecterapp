<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersonsHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    /**
     * @return array<string, PersonResponse> by person id
     */
    public function __invoke(ConsultPersons $query): array
    {
        $reader = $this->readers->get(new MemberId($query->readerId));
        $responses = [];

        foreach ($this->persons->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->ids)) as $person) {
            $response = $this->reachable($person, $reader);

            if (null !== $response) {
                $responses[$response->id] = $response;
            }
        }

        return $responses;
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
