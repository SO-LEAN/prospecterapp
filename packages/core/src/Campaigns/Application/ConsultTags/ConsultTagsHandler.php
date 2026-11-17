<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The tags put by hand on an organization the account of the member prospects, or on one of its contacts.
 */
final readonly class ConsultTagsHandler implements QueryHandler
{
    public function __construct(
        private TagsRetriever $tagsRetriever,
        private Taggers $taggers,
        private TaggableHolders $holders,
    ) {
    }

    public function __invoke(ConsultTags $query, ConsultTagsOutput $output): void
    {
        $accountId = $this->taggers->accountOf(new MemberId($query->memberId));
        $holder = Holder::of($query->holder, $query->holderId);
        $this->holders->ensureKnownBy($accountId, $holder);
        $output->present($this->tagsRetriever->of($accountId, $holder));
    }
}
