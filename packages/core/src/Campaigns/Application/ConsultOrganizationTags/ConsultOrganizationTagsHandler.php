<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The tags put by hand on an organization the account of the member prospects; those of the contacts are
 * `ConsultContactTags`.
 */
final readonly class ConsultOrganizationTagsHandler implements QueryHandler
{
    public function __construct(
        private TagsRetriever $tagsRetriever,
        private Taggers $taggers,
        private TaggableHolders $holders,
    ) {
    }

    public function __invoke(ConsultOrganizationTags $query, ConsultOrganizationTagsOutput $output): void
    {
        $accountId = $this->taggers->accountOf(new MemberId($query->memberId));
        $holder = Holder::organization(new OrganizationId($query->organizationId));
        $this->holders->ensureKnownBy($accountId, $holder);
        $output->present($this->tagsRetriever->of($accountId, $holder));
    }
}
