<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

use Prospecter\Core\Prospecting\Domain\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultTimelineHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultTimeline $query): TimelineResponse
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new PersonId($query->personId);
        $this->contactOrganizations->of($contact, $prospector);
        $prospector->ensureMayExchangeWithTheContacts();

        return TimelineResponse::of($this->exchanges->timelineOf($contact, $prospector->accountId, $query->after, $query->max));
    }
}
