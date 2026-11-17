<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The tagging of an organization or of a contact (ADR 0138): `ACCOUNT#<account>` / `TAGGING#<kind>#<id>`, its tags
 * by their names.
 */
final readonly class DynamoTaggingRepository implements TaggingRepository
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function find(AccountId $accountId, Holder $holder): ?Tagging
    {
        $document = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))['tagging'] ?? null;

        return null === $document ? null : Hydrator::restore(Tagging::class, [
            'accountId' => $accountId,
            'holder' => $holder,
            'tags' => array_map(Tag::written(...), $document['tags']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
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
                'tags' => array_map(static fn (Tag $tag): string => $tag->name, $state['tags']),
                'createdAt' => Documents::moment($state['createdAt']),
                'modifiedAt' => Documents::moment($state['modifiedAt']),
            ],
        ])]);
    }

    public function remove(Tagging $tagging): void
    {
        $holder = $tagging->holder();
        $this->campaignsTable->transact([$this->campaignsTable->deleting(CampaignsKeys::account($tagging->accountId()->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))]);
    }
}
