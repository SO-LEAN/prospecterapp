<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultUpcomingExchanges;

use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline\TimelineOutput;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultUpcomingExchangesHandler implements QueryHandler
{
    public function __construct(
        private TimelineRetriever $timelineRetriever,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultUpcomingExchanges $query, TimelineOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new ContactId($query->personId);
        $this->contactOrganizations->ensureIsAContactOfTheAccount($contact, $prospector->accountId);

        $timeline = $this->timelineRetriever->upcomingWithContact($contact, $prospector, $query->asOf, $query->after, $query->max);
        $offered = array_map(static fn (TimelineIntent $intent): string => $intent->name, $this->contactOrganizations->timelineIntentsOf($contact, $prospector));

        $output->present($timeline->offering($offered));
    }
}
