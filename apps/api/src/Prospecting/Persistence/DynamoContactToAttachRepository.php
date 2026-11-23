<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;

/**
 * Stores the contacts to attach in Prospecting's table (ADR 0048, 0146), as `CONTACT_TO_ATTACH#<person>` /
 * `CONTACT_TO_ATTACH`. `GSI1` lists them among their holder's contacts (`TO_ATTACH#<member>`). `GSI2` lists them among
 * every account's contacts (`UNATTACHED_ERASURE`). Both indexes sort them by the moment from which they are erased,
 * then by their person.
 */
final readonly class DynamoContactToAttachRepository implements ContactToAttachRepository
{
    public const string CONTACT_TO_ATTACH = 'CONTACT_TO_ATTACH';
    public const string ERASURE = 'UNATTACHED_ERASURE';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function find(ContactId $contact): ?ContactToAttach
    {
        $item = $this->prospectingTable->item(self::key($contact->value), self::CONTACT_TO_ATTACH);

        return null === $item ? null : ContactToAttachDocument::contactToAttach($item['contactToAttach']);
    }

    public function save(ContactToAttach $contactToAttach): void
    {
        $document = ContactToAttachDocument::of($contactToAttach);
        $id = Typed::string($document['id']);
        $position = Documents::moment(ContactToAttachDocument::erasableFrom($document)).'#'.$id;

        $this->prospectingTable->transact([$this->prospectingTable->putting([
            'PK' => self::key($id),
            'SK' => self::CONTACT_TO_ATTACH,
            'GSI1PK' => self::heldBy(Typed::string($document['holder'])),
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
