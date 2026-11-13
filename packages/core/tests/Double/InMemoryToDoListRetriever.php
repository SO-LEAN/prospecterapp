<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the to-dos the in-memory repository saved, as the adapter reads them, without their getters.
 */
final readonly class InMemoryToDoListRetriever implements ToDoListRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ToDoId $id): ToDoInList
    {
        return $this->inList(static fn (array $state): bool => $state['id']->equals($id))[0] ?? throw UnknownToDo::identifiedBy($id);
    }

    public function forContact(PersonId $contact, AccountId $accountId): array
    {
        return $this->inList(static fn (array $state): bool => true === $state['contact']?->equals($contact) && $state['accountId']->equals($accountId));
    }

    public function keptFor(OrganizationId $organization): array
    {
        return $this->inList(static fn (array $state): bool => array_any($state['keptFor'], $organization->equals(...)));
    }

    public function stillToDoBy(MemberId $holder): array
    {
        return $this->inList(static fn (array $state): bool => null === $state['doneAt'] && $state['createdBy']->equals($holder));
    }

    /**
     * @param \Closure(array<string, mixed>): bool $kept
     *
     * @return list<ToDoInList>
     */
    private function inList(\Closure $kept): array
    {
        $states = array_filter(array_map(StateOf::the(...), $this->database->toDos()), $kept);

        return array_values(array_map(static fn (array $state): ToDoInList => new ToDoInList(
            $state['id']->value,
            $state['accountId']->value,
            $state['contact']?->value,
            array_map(static fn (OrganizationId $organization): string => $organization->value, $state['keptFor']),
            $state['text'],
            $state['due']->day,
            $state['due']->time,
            $state['doneAt'],
            $state['createdBy']->value,
            array_map(static fn (ToDoIntent $intent): string => $intent->name, ToDoPossibilities::given(null !== $state['doneAt'])),
        ), $states));
    }
}
