<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

use Prospecter\Core\Campaigns\Application\Port\TagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The tags of an organization that the member's account prospects: the tags put by hand, and the automatic tags for
 * where it is (ADR 0142). `ConsultContactTags` gives the contacts' tags.
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
        $organizationId = new OrganizationId($query->organizationId);
        $holder = Holder::organization($organizationId);
        $this->holders->ensureKnownBy($accountId, $holder);
        $automatic = Locality::read($this->tagsRetriever->localityOf($accountId, $organizationId))->geographicTags();
        $output->present($this->tagsRetriever->of($accountId, $holder), array_map(strval(...), $automatic));
    }
}
