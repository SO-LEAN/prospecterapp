<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

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

    public function localityOf(AccountId $accountId, OrganizationId $organizationId): ?array
    {
        $fact = array_find($this->database->campaignsFactsOf($accountId->value), static fn (object $fact): bool => $fact instanceof OrganizationFact && $fact->id->equals($organizationId));

        return $fact instanceof OrganizationFact ? self::localityIn($fact) : null;
    }

    public function localitiesIn(AccountId $accountId): array
    {
        $present = array_filter($this->database->campaignsFactsOf($accountId->value), static fn (object $fact): bool => $fact instanceof OrganizationFact && $fact->present);

        return array_values(array_map(self::localityIn(...), $present));
    }

    /**
     * @return array{municipalityCode: ?string, country: ?string}
     */
    private static function localityIn(OrganizationFact $fact): array
    {
        return ['municipalityCode' => $fact->locality->municipalityCode, 'country' => $fact->locality->country];
    }

    /**
     * @return list<string>
     */
    private static function tagsOf(Tagging $tagging): array
    {
        return array_map(static fn (Tag $tag): string => (string) $tag, StateOf::the($tagging)->listOf('tags', Tag::class));
    }
}
