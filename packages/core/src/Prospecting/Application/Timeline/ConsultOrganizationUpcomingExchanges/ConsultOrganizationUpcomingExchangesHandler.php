<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationUpcomingExchanges;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimelineOutput;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultOrganizationUpcomingExchangesHandler implements QueryHandler
{
    public function __construct(
        private TimelineRetriever $timelineRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultOrganizationUpcomingExchanges $query, OrganizationTimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $timeline = $this->timelineRetriever->upcomingInOrganization($organization, $prospector, $query->asOf, $query->after, $query->max);

        $output->present(new OrganizationTimeline($timeline, $organization));
    }
}
