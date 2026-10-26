<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline;

use Prospecter\Core\Prospecting\Application\TimelineOutput;
use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ConsultOrganizationTimelineHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
    ) {
    }

    public function __invoke(ConsultOrganizationTimeline $query, TimelineOutput $output): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($query->organizationId));

        $output->present(TimelineResponse::ofOrganization(
            $organization,
            $this->exchanges->timelineOfOrganization($organization->id(), $query->after, $query->max),
            $this->exchanges->countToMakeConformFor($organization->id()),
            $prospector,
        ));
    }
}
