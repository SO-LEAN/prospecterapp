<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\ConsultMyBookingPage;

use Prospecter\Core\Booking\Application\Port\MyBookingPageRetriever;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultMyBookingPageHandler implements QueryHandler
{
    public function __construct(
        private MyBookingPageRetriever $pages,
        private ConnectedCalendars $calendars,
    ) {
    }

    public function __invoke(ConsultMyBookingPage $query, ConsultMyBookingPageOutput $output): void
    {
        $host = new MemberId($query->memberId);
        $today = $query->at->setTimezone(new \DateTimeZone(new HostTimeZone($query->timeZone)->name))->format('Y-m-d');

        $output->present($this->pages->of($host, $today, $this->calendars->isConnectedFor($host)));
    }
}
