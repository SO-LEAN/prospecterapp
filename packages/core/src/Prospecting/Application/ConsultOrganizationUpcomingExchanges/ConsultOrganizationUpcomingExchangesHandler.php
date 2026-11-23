<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\TimelineOutput;
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

    public function __invoke(ConsultOrganizationUpcomingExchanges $query, TimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $output->present($this->timelineRetriever->upcomingInOrganization($organization, $prospector, $query->asOf, $query->after, $query->max));
    }
}
