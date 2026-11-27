<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationTimeline;

use Prospecter\Core\Prospecting\Application\Port\MailCatchUpRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline\CampaignsOfTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimelineOutput;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultOrganizationTimelineHandler implements QueryHandler
{
    public function __construct(
        private TimelineRetriever $timelineRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private Prospectors $prospectors,
        private CampaignsOfTimeline $campaignsOfTimeline,
        private MailCatchUpRetriever $mailCatchUpRetriever,
    ) {
    }

    public function __invoke(ConsultOrganizationTimeline $query, OrganizationTimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $timeline = $this->campaignsOfTimeline->named($this->timelineRetriever->ofOrganization($organization, $prospector, $query->asOf, $query->after, $query->max), $prospector->accountId);

        $output->present(new OrganizationTimeline(
            $timeline,
            $organization,
            $this->mailCatchUpRetriever->intentsOn($prospector->id, new OrganizationId($organization->id)),
        ));
    }
}
