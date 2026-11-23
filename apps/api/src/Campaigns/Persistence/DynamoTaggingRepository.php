<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * An organization's or a contact's tagging (ADR 0138): `ACCOUNT#<account>` / `TAGGING#<kind>#<id>`, with its tags by
 * name.
 */
final readonly class DynamoTaggingRepository implements TaggingRepository
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function find(AccountId $accountId, Holder $holder): ?Tagging
    {
        $stored = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))['tagging'] ?? null;

        if (null === $stored) {
            return null;
        }

        $document = Typed::document($stored);

        return Hydrator::restore(Tagging::class, [
            'accountId' => $accountId,
            'holder' => $holder,
            'tags' => array_map(Tag::written(...), Typed::strings($document['tags'])),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    public function save(Tagging $tagging): void
    {
        $state = Hydrator::stateOf($tagging);
        $holder = $tagging->holder();

        $this->campaignsTable->transact([$this->campaignsTable->putting([
            'PK' => CampaignsKeys::account($tagging->accountId()->value),
            'SK' => CampaignsKeys::tagging($holder->kind->value, $holder->id->value),
            'tagging' => [
                'accountId' => $tagging->accountId()->value,
                'holder' => $holder->kind->value,
                'holderId' => $holder->id->value,
                'tags' => array_map(static fn (Tag $tag): string => $tag->name, Typed::objects($state['tags'], Tag::class)),
                'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
                'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
            ],
        ])]);
    }

    public function remove(Tagging $tagging): void
    {
        $holder = $tagging->holder();
        $this->campaignsTable->transact([$this->campaignsTable->deleting(CampaignsKeys::account($tagging->accountId()->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))]);
    }
}
