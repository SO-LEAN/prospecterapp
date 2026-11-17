<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The DynamoDB retriever in the scenarios: the tags of what the repository saved, read without its getters.
 */
final readonly class InMemoryTagsRetriever implements TagsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(AccountId $accountId, Holder $holder): array
    {
        $tagging = $this->database->tagging($accountId->value, $holder);

        return null === $tagging ? [] : self::tagsOf($tagging);
    }

    public function carriedIn(AccountId $accountId): array
    {
        return array_values(array_unique(array_merge([], ...array_map(self::tagsOf(...), $this->database->taggingsOf($accountId->value)))));
    }

    /**
     * @return list<string>
     */
    private static function tagsOf(Tagging $tagging): array
    {
        return array_map(static fn (Tag $tag): string => (string) $tag, StateOf::the($tagging)['tags']);
    }
}
