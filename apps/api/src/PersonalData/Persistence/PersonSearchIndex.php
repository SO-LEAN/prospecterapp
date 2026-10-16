<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\SearchProjection;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Keeps the search of the persons up to date (ADR 0050): whatever happened to a person, their words are
 * indexed again among the persons their account keeps for the same purpose, or forgotten when erased.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class PersonSearchIndex
{
    public function __construct(
        private PersonRepository $persons,
        private SearchProjection $personalDataSearch,
        private Table $personalDataTable,
    ) {
    }

    public function __invoke(PersonEvent $event): void
    {
        try {
            $this->index($this->persons->get($event->person));
        } catch (UnknownPerson) {
            $this->personalDataSearch->forget($event->person->value);
        }
    }

    /**
     * Indexes every person again, after forgetting the whole projection: what repairs a drift.
     */
    public function rebuild(): int
    {
        $this->personalDataSearch->clear();
        $persons = array_map(static fn (array $item): Person => PersonDocument::person($item['person']), $this->personalDataTable->scan('PERSON#'));
        array_map($this->index(...), $persons);

        return count($persons);
    }

    private function index(Person $person): void
    {
        $this->personalDataSearch->index((string) $person->id(), DynamoPersonRepository::scopeOf($person->accountId(), $person->purpose()), $person->searchTerms());
    }
}
