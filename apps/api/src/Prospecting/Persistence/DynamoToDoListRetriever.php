<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the to-dos from the items `DynamoToDoRepository` writes, without restoring them (ADR 0088).
 */
final readonly class DynamoToDoListRetriever implements ToDoListRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ToDoId $id): ToDoInList
    {
        $item = $this->prospectingTable->item(ToDoKeys::toDo($id->value), ToDoKeys::TO_DO) ?? throw UnknownToDo::identifiedBy($id);

        return ToDoDocument::inList($item['toDo']);
    }

    public function forContact(PersonId $contact, AccountId $accountId): array
    {
        return self::inList($this->prospectingTable->query(ToDoKeys::ofContact($accountId->value, $contact->value), Table::BY_GSI1));
    }

    public function keptFor(OrganizationId $organization): array
    {
        return self::inList(DynamoToDoRepository::itemsKeptFor($this->prospectingTable, $organization->value));
    }

    public function stillToDoBy(MemberId $holder): array
    {
        return self::inList($this->prospectingTable->query(ToDoKeys::stillToDoBy($holder->value), Table::BY_GSI2));
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<ToDoInList>
     */
    private static function inList(array $items): array
    {
        return array_map(static fn (array $item): ToDoInList => ToDoDocument::inList($item['toDo']), $items);
    }
}
