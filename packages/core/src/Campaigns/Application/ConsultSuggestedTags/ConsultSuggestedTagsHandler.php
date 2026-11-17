<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultSuggestedTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Application\SuggestedTags;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Policy\PredefinedTags;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The tags proposed to the account of the member: the predefined ones, then those its organizations and contacts
 * carry; a tag nothing carries any more is no longer proposed (ADR 0138).
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
        $carried = $this->tagsRetriever->carriedIn($this->taggers->accountOf(new MemberId($query->memberId)));
        $own = array_values(array_filter(array_unique($carried), static fn (string $tag): bool => !PredefinedTags::include($tag)));
        sort($own);
        $output->present(new SuggestedTags(PredefinedTags::byFamily(), $own, array_map(strval(...), Reach::everyAutomaticTag())));
    }
}
