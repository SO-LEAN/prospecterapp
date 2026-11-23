<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the contacts to attach from the items that `DynamoContactToAttachRepository` writes, without restoring them
 * (ADR 0088). It reads a holder's contacts in `GSI1`. It reads in `GSI2` the contacts of every account that are left
 * unattached at a given moment, by the moment from which they are erased.
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
        $document = null === $item ? null : Typed::document($item['contactToAttach']);

        return null !== $document && $holder->value === $document['holder'] ? ContactToAttachDocument::inList($document) : null;
    }

    public function leftUnattachedAt(\DateTimeImmutable $at): array
    {
        $items = $this->prospectingTable->queryBetween(DynamoContactToAttachRepository::ERASURE, Table::BY_GSI2, self::FIRST, Documents::moment($at).self::AFTER_EVERY_PERSON);

        return array_map(static fn (array $item): string => Typed::string(Typed::document($item['contactToAttach'])['id']), $items);
    }
}
