<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
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
     * Reads the links in the holder's calendar between the period's first and last days, in one query. It leaves out a
     * to-do erased meanwhile.
     */
    public function dueIn(MemberId $holder, CalendarPeriod $period): array
    {
        $links = $this->prospectingTable->queryBetween(ToDoKeys::calendar($holder->value), Table::BY_GSI1, ToDoKeys::dayStart($period->firstDay()), ToDoKeys::dayEnd($period->lastDay()));

        return self::inList($this->prospectingTable->itemsInTheOrderOf(Typed::strings(array_column($links, 'SK')), ToDoKeys::TO_DO));
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
