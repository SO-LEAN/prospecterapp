<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contacts to attach read from the items of `DynamoContactToAttachRepository`, without restoring them (ADR 0088):
 * those of a holder in `GSI1`, those of every account left unattached at a moment in `GSI2`, by the moment they are
 * erased from.
 */
final readonly class DynamoContactsToAttachRetriever implements ContactsToAttachRetriever
{
    private const string FIRST = '0';
    private const string AFTER_EVERY_PERSON = '#~';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function heldBy(MemberId $holder): array
    {
        $items = $this->prospectingTable->queryAfter(DynamoContactToAttachRepository::heldBy($holder->value), Table::BY_GSI1, self::FIRST);

        return array_map(static fn (array $item): ContactToAttachInList => ContactToAttachDocument::inList($item['contactToAttach']), $items);
    }

    public function findHeldBy(MemberId $holder, PersonId $person): ?ContactToAttachInList
    {
        $item = $this->prospectingTable->item(DynamoContactToAttachRepository::key($person->value), DynamoContactToAttachRepository::CONTACT_TO_ATTACH);

        return $holder->value === ($item['contactToAttach']['holder'] ?? null) ? ContactToAttachDocument::inList($item['contactToAttach']) : null;
    }

    public function leftUnattachedAt(\DateTimeImmutable $at): array
    {
        $items = $this->prospectingTable->queryBetween(DynamoContactToAttachRepository::ERASURE, Table::BY_GSI2, self::FIRST, Documents::moment($at).self::AFTER_EVERY_PERSON);

        return array_map(static fn (array $item): string => $item['contactToAttach']['id'], $items);
    }
}
