<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

use Prospecter\Core\Prospecting\Application\TimelineOutput;
use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultTimelineHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultTimeline $query, TimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new PersonId($query->personId);
        $this->contactOrganizations->ensureIsAContactOfTheAccount($contact, $prospector->accountId);

        $output->present(TimelineResponse::of($this->exchanges->timelineOf($contact, $prospector->accountId, $query->after, $query->max), $prospector));
    }
}
