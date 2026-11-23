<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline;

use Prospecter\Core\Prospecting\Application\CampaignsOfTimeline;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\TimelineOutput;
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
    ) {
    }

    public function __invoke(ConsultOrganizationTimeline $query, TimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $output->present($this->campaignsOfTimeline->named($this->timelineRetriever->ofOrganization($organization, $prospector, $query->asOf, $query->after, $query->max), $prospector->accountId));
    }
}
