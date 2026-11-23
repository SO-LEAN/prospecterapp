<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Stores the to-dos in Prospecting's table (ADR 0048, 0113):
 * - `TODO#<id>` / `TODO`: the to-do. When it has a contact, `GSI1` lists it among its contact's to-dos
 *   (`CONTACT_TODOS#<account>#<person>`). Until it is done, `GSI2` lists it among the to-dos its holder still has to do
 *   (`STILL_TO_DO#<member>`).
 * - `TODOS#<organization>` / `TODO#<id>`: a to-do kept for an organization, written with the to-do.
 * - `TODO_CALENDAR#<member>` / `TODO#<id>`: the to-do in its holder's calendar, done or not. `GSI1` lists it
 *   (`TODO_CALENDAR#<member>`) under its due date as written: its day, then its hour, then its id (ADR 0134).
 * A list of to-dos is read whole, because each list holds only a handful.
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
            ...$this->linksToItsOrganizations($toDo->id()->value, Typed::strings($document['keptFor']), self::storedKeptFor($stored)),
            $this->prospectingTable->putting(self::linkInTheCalendarOfItsHolder($document)),
        ]);
    }

    public function remove(ToDo $toDo): void
    {
        $id = $toDo->id()->value;
        $stored = $this->prospectingTable->item(ToDoKeys::toDo($id), ToDoKeys::TO_DO);

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(ToDoKeys::toDo($id), ToDoKeys::TO_DO),
            ...$this->linksToItsOrganizations($id, [], self::storedKeptFor($stored)),
            $this->prospectingTable->deleting(ToDoKeys::calendar(Typed::string(ToDoDocument::of($toDo)['createdBy'])), ToDoKeys::toDo($id)),
        ]);
    }

    /**
     * @param array<string, mixed>|null $stored the to-do's item as stored, null for a new to-do
     *
     * @return list<string>
     */
    private static function storedKeptFor(?array $stored): array
    {
        return null === $stored ? [] : Typed::strings(Typed::document($stored['toDo'])['keptFor'] ?? []);
    }

    public function keptFor(OrganizationId $organization): array
    {
        return array_map(static fn (array $item): ToDo => ToDoDocument::toDo($item['toDo']), self::itemsKeptFor($this->prospectingTable, $organization->value));
    }

    public function forContact(ContactId $contact, AccountId $accountId): array
    {
        return array_map(static fn (array $item): ToDo => ToDoDocument::toDo($item['toDo']), $this->prospectingTable->query(ToDoKeys::ofContact($accountId->value, $contact->value), Table::BY_GSI1));
    }

    /**
     * Writes again every to-do's link in its holder's calendar. A table written before the to-dos were in the calendars
     * needs this once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = $this->prospectingTable->scan(ToDoKeys::TO_DO.'#');
        $this->prospectingTable->batch(array_map(static fn (array $item): array => self::linkInTheCalendarOfItsHolder(Typed::document($item['toDo'])), $items));

        return count($items);
    }

    /**
     * Reads the items of the to-dos kept for the organization, through its links.
     *
     * @return list<array<string, mixed>>
     */
    public static function itemsKeptFor(Table $prospectingTable, string $organization): array
    {
        $links = $prospectingTable->query(ToDoKeys::keptFor($organization));

        return $prospectingTable->items(array_map(static fn (string $toDo): array => [$toDo, ToDoKeys::TO_DO], Typed::strings(array_column($links, 'SK'))));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, ...}
     */
    private function itemOf(array $document): array
    {
        $id = Typed::string($document['id']);
        $contact = Typed::stringOrNull($document['contact']);
        $amongThoseOfItsContact = null === $contact ? [] : [
            'GSI1PK' => ToDoKeys::ofContact(Typed::string($document['accountId']), $contact),
            'GSI1SK' => $id,
        ];
        $stillToDoByItsHolder = null !== $document['doneAt'] ? [] : [
            'GSI2PK' => ToDoKeys::stillToDoBy(Typed::string($document['createdBy'])),
            'GSI2SK' => $id,
        ];

        return [
            'PK' => ToDoKeys::toDo($id),
            'SK' => ToDoKeys::TO_DO,
            ...$amongThoseOfItsContact,
            ...$stillToDoByItsHolder,
            'toDo' => $document,
        ];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, GSI1PK: string, GSI1SK: string}
     */
    private static function linkInTheCalendarOfItsHolder(array $document): array
    {
        $calendar = ToDoKeys::calendar(Typed::string($document['createdBy']));

        return ['PK' => $calendar, 'SK' => ToDoKeys::toDo(Typed::string($document['id'])), 'GSI1PK' => $calendar, 'GSI1SK' => ToDoKeys::calendarPositionOf($document)];
    }

    /**
     * Returns the links to write and to delete, so that the to-do is kept for the given organizations.
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
