<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultSuggestedTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Application\SuggestedTags;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Policy\PredefinedTags;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The tags proposed to the account of the member: the predefined ones, then those its organizations and contacts
 * carry; a tag nothing carries any more is no longer proposed (ADR 0138); and the automatic tags to search by: those of
 * how a contact is reached, then the geographic tags of its organizations (ADR 0142).
 */
final readonly class ConsultSuggestedTagsHandler implements QueryHandler
{
    public function __construct(
        private TagsRetriever $tagsRetriever,
        private Taggers $taggers,
    ) {
    }

    public function __invoke(ConsultSuggestedTags $query, ConsultSuggestedTagsOutput $output): void
    {
        $accountId = $this->taggers->accountOf(new MemberId($query->memberId));
        $carried = $this->tagsRetriever->carriedIn($accountId);
        $own = array_values(array_filter(array_unique($carried), static fn (string $tag): bool => !PredefinedTags::include($tag)));
        sort($own);
        $output->present(new SuggestedTags(PredefinedTags::byFamily(), $own, [...array_map(strval(...), Reach::everyAutomaticTag()), ...$this->geographicTagsIn($accountId)]));
    }

    /**
     * @return list<string> those of where its organizations are, once each, in the order of their names
     */
    private function geographicTagsIn(AccountId $accountId): array
    {
        $tags = array_map(strval(...), array_merge([], ...array_map(static fn (array $locality): array => Locality::read($locality)->geographicTags(), $this->tagsRetriever->localitiesIn($accountId))));
        $tags = array_values(array_unique($tags));
        sort($tags);

        return $tags;
    }
}
