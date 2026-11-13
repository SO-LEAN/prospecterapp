<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultCalendarHandler implements QueryHandler
{
    public function __construct(
        private CalendarRetriever $calendarRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultCalendar $query, ConsultCalendarOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $period = new CalendarPeriod($query->from, $query->until);

        $output->present(new Calendar($this->calendarRetriever->heldBy($holder, $period)));
    }
}
