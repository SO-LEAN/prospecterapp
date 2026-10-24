<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform;

use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ConsultExchangesToMakeConformHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
    ) {
    }

    public function __invoke(ConsultExchangesToMakeConform $query): TimelineResponse
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($query->organizationId));

        return TimelineResponse::ofOrganization(
            $organization,
            $this->exchanges->toMakeConformFor($organization->id(), $query->after, $query->max),
            $this->exchanges->countToMakeConformFor($organization->id()),
            $prospector,
        );
    }
}
