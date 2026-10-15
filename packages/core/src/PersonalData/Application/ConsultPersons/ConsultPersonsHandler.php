<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\PersonalData\Domain\UnknownPerson;

/**
 * The batch read a controller makes to show a page: one call for every person of the page. A person the
 * reader may not reach is left out, as if it did not exist.
 */
final readonly class ConsultPersonsHandler
{
    public function __construct(private PersonRepository $persons, private Readers $readers)
    {
    }

    /**
     * @return array<string, PersonResponse> by person id
     */
    public function __invoke(ConsultPersons $query): array
    {
        $reader = $this->readers->get($query->readerId);
        $responses = [];

        foreach ($this->persons->ofIds($query->ids) as $person) {
            try {
                $responses[$person->id()->value] = PersonResponse::of($person, $reader);
            } catch (UnknownPerson) {
                continue;
            }
        }

        return $responses;
    }
}
