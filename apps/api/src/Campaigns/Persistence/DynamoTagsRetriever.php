<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The tags put by hand, read from the items of `DynamoTaggingRepository` without restoring them (ADR 0088): those of
 * an organization or of a contact, and all those of an account, its partition read at once.
 */
final readonly class DynamoTagsRetriever implements TagsRetriever
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function of(AccountId $accountId, Holder $holder): array
    {
        $document = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))['tagging'] ?? null;

        return null === $document ? [] : self::marked($document['tags']);
    }

    public function carriedIn(AccountId $accountId): array
    {
        $taggings = array_column($this->campaignsTable->query(CampaignsKeys::account($accountId->value)), 'tagging');

        return array_values(array_unique(self::marked(array_merge([], ...array_column($taggings, 'tags')))));
    }

    /**
     * @param list<string> $names
     *
     * @return list<string>
     */
    private static function marked(array $names): array
    {
        return array_map(static fn (string $name): string => Tag::MARK.$name, $names);
    }
}
