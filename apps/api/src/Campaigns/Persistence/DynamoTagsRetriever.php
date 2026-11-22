<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The tags put by hand, read from the items of `DynamoTaggingRepository` without restoring them (ADR 0088): those of
 * an organization or of a contact, and all those of an account, its partition read at once; where the organizations
 * are, from the items of their facts (`DynamoFactRepository`, ADR 0142).
 */
final readonly class DynamoTagsRetriever implements TagsRetriever
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function of(AccountId $accountId, Holder $holder): array
    {
        $document = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::tagging($holder->kind->value, $holder->id->value))['tagging'] ?? null;

        return null === $document ? [] : self::marked(Typed::strings(Typed::document($document)['tags']));
    }

    public function carriedIn(AccountId $accountId): array
    {
        $taggings = Typed::documents(array_column($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId->value), null, ...CampaignsKeys::taggings()), 'tagging'));

        return array_values(array_unique(self::marked(array_merge([], ...array_map(static fn (array $tagging): array => Typed::strings($tagging['tags']), $taggings)))));
    }

    public function localityOf(AccountId $accountId, OrganizationId $organizationId): ?array
    {
        $item = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::organizationFact($organizationId->value));

        return null === $item ? null : OrganizationFactItem::localityIn($item);
    }

    public function localitiesIn(AccountId $accountId): array
    {
        $facts = $this->campaignsTable->queryBetween(CampaignsKeys::account($accountId->value), null, ...CampaignsKeys::organizationFacts());

        return array_values(array_map(OrganizationFactItem::localityIn(...), array_filter($facts, static fn (array $fact): bool => (bool) $fact['present'])));
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
