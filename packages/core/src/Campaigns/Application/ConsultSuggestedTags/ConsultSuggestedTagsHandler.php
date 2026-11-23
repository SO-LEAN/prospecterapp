<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultSuggestedTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Application\SuggestedTags;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Rule\PredefinedTags;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The tags proposed to the member's account: the predefined tags, then the tags its organizations and contacts carry. A
 * tag that nothing carries any more is no longer proposed (ADR 0138). It also proposes the automatic tags to search by:
 * the tags for how a contact is reached, then its organizations' geographic tags (ADR 0142).
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
     * @return list<string> the tags for where its organizations are, once each, sorted by name
     */
    private function geographicTagsIn(AccountId $accountId): array
    {
        $carried = [];

        foreach ($this->tagsRetriever->localitiesIn($accountId) as $locality) {
            foreach (Locality::read($locality)->geographicTags() as $tag) {
                $carried[(string) $tag] = true;
            }
        }

        $tags = array_map(strval(...), array_keys($carried));
        sort($tags);

        return $tags;
    }
}
