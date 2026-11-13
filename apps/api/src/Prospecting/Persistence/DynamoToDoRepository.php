<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The to-dos in the table of Prospecting (ADR 0048, 0112):
 * - `TODO#<id>` / `TODO`: the to-do, listed in `GSI1` among those of its contact (`CONTACT_TODOS#<account>#<person>`)
 *   when it has one, and in `GSI2` among those its holder still has to do (`STILL_TO_DO#<member>`) until it is done;
 * - `TODOS#<organization>` / `TODO#<id>`: the to-dos kept for an organization, written with it.
 * A list of to-dos is read whole: a handful each.
 */
final readonly class DynamoToDoRepository implements ToDoRepository
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ToDoId $id): ToDo
    {
        $item = $this->prospectingTable->item(ToDoKeys::toDo($id->value), ToDoKeys::TO_DO) ?? throw UnknownToDo::identifiedBy($id);

        return ToDoDocument::toDo($item['toDo']);
    }

    public function save(ToDo $toDo): void
    {
        $document = ToDoDocument::of($toDo);
        $stored = $this->prospectingTable->item(ToDoKeys::toDo($toDo->id()->value), ToDoKeys::TO_DO);

        $this->prospectingTable->transact([
            $this->prospectingTable->putting($this->itemOf($document)),
            ...$this->linksToItsOrganizations($document['id'], $document['keptFor'], $stored['toDo']['keptFor'] ?? []),
        ]);
    }

    public function remove(ToDo $toDo): void
    {
        $id = $toDo->id()->value;
        $stored = $this->prospectingTable->item(ToDoKeys::toDo($id), ToDoKeys::TO_DO);

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(ToDoKeys::toDo($id), ToDoKeys::TO_DO),
            ...$this->linksToItsOrganizations($id, [], $stored['toDo']['keptFor'] ?? []),
        ]);
    }

    public function keptFor(OrganizationId $organization): array
    {
        return array_map(static fn (array $item): ToDo => ToDoDocument::toDo($item['toDo']), self::itemsKeptFor($this->prospectingTable, $organization->value));
    }

    public function forContact(PersonId $contact, AccountId $accountId): array
    {
        return array_map(static fn (array $item): ToDo => ToDoDocument::toDo($item['toDo']), $this->prospectingTable->query(ToDoKeys::ofContact($accountId->value, $contact->value), Table::BY_GSI1));
    }

    /**
     * The items of the to-dos kept for the organization, through its links.
     *
     * @return list<array<string, mixed>>
     */
    public static function itemsKeptFor(Table $prospectingTable, string $organization): array
    {
        $links = $prospectingTable->query(ToDoKeys::keptFor($organization));

        return $prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], ToDoKeys::TO_DO], $links));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(array $document): array
    {
        $amongThoseOfItsContact = null === $document['contact'] ? [] : [
            'GSI1PK' => ToDoKeys::ofContact($document['accountId'], $document['contact']),
            'GSI1SK' => $document['id'],
        ];
        $stillToDoByItsHolder = null !== $document['doneAt'] ? [] : [
            'GSI2PK' => ToDoKeys::stillToDoBy($document['createdBy']),
            'GSI2SK' => $document['id'],
        ];

        return [
            'PK' => ToDoKeys::toDo($document['id']),
            'SK' => ToDoKeys::TO_DO,
            ...$amongThoseOfItsContact,
            ...$stillToDoByItsHolder,
            'toDo' => $document,
        ];
    }

    /**
     * The links to write and to delete so that the organizations the to-do is kept for are those given.
     *
     * @param list<string> $keptFor
     * @param list<string> $storedKeptFor
     *
     * @return list<Write>
     */
    private function linksToItsOrganizations(string $id, array $keptFor, array $storedKeptFor): array
    {
        $toDo = ToDoKeys::toDo($id);

        return [
            ...array_map(fn (string $organization): Write => $this->prospectingTable->putting(['PK' => ToDoKeys::keptFor($organization), 'SK' => $toDo]), $keptFor),
            ...array_map(fn (string $organization): Write => $this->prospectingTable->deleting(ToDoKeys::keptFor($organization), $toDo), array_values(array_diff($storedKeptFor, $keptFor))),
        ];
    }
}
