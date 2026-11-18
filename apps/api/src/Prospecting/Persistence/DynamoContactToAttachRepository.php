<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contacts to attach in the table of Prospecting (ADR 0048, 0146): `CONTACT_TO_ATTACH#<person>` /
 * `CONTACT_TO_ATTACH`, listed in `GSI1` among those of their holder (`TO_ATTACH#<member>`) and in `GSI2` among those
 * of every account (`UNATTACHED_ERASURE`), both under the moment they are erased from, then their person.
 */
final readonly class DynamoContactToAttachRepository implements ContactToAttachRepository
{
    public const string CONTACT_TO_ATTACH = 'CONTACT_TO_ATTACH';
    public const string ERASURE = 'UNATTACHED_ERASURE';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function find(PersonId $contact): ?ContactToAttach
    {
        $item = $this->prospectingTable->item(self::key($contact->value), self::CONTACT_TO_ATTACH);

        return null === $item ? null : ContactToAttachDocument::contactToAttach($item['contactToAttach']);
    }

    public function save(ContactToAttach $contactToAttach): void
    {
        $document = ContactToAttachDocument::of($contactToAttach);
        $position = Documents::moment(ContactToAttachDocument::erasableFrom($document)).'#'.$document['id'];

        $this->prospectingTable->transact([$this->prospectingTable->putting([
            'PK' => self::key($document['id']),
            'SK' => self::CONTACT_TO_ATTACH,
            'GSI1PK' => self::heldBy($document['holder']),
            'GSI1SK' => $position,
            'GSI2PK' => self::ERASURE,
            'GSI2SK' => $position,
            'contactToAttach' => $document,
        ])]);
    }

    public function remove(ContactToAttach $contactToAttach): void
    {
        $this->prospectingTable->transact([$this->prospectingTable->deleting(self::key($contactToAttach->id()->value), self::CONTACT_TO_ATTACH)]);
    }

    public static function key(string $person): string
    {
        return self::CONTACT_TO_ATTACH.'#'.$person;
    }

    public static function heldBy(string $member): string
    {
        return 'TO_ATTACH#'.$member;
    }
}
