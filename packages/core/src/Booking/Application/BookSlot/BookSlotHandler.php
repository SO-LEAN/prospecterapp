<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookSlot;

use Prospecter\Core\Booking\Domain\Exception\BookingUnavailable;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Model\Booker;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\Booking\Domain\Service\BusyPeriods;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class BookSlotHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPageRepository $pages,
        private AppointmentRepository $appointments,
        private ConnectedCalendars $calendars,
        private BusyPeriods $busyPeriods,
        private Events $events,
    ) {
    }

    public function __invoke(BookSlot $command): void
    {
        $page = $this->bookablePageAt(new BookingCode($command->code));
        $appointment = $page->book(
            new AppointmentId($command->appointmentId),
            new PersonId($command->bookerId),
            $command->slot,
            Channel::from($command->channel),
            Booker::named($command->firstName, $command->lastName, $command->phone, $command->email),
            $command->subject,
            $this->busyPeriods->of($page->accountId(), $page->host(), $command->executedAt),
            $this->appointments->toComeOf($page->host(), $command->executedAt),
            $command->executedAt,
        );
        $this->appointments->save($appointment);
        $this->publishWhatHappenedTo($appointment);
    }

    /**
     * @throws BookingUnavailable
     */
    private function bookablePageAt(BookingCode $code): BookingPage
    {
        $page = $this->pages->findByCode($code);
        if (null === $page || !$page->isOpen() || !$this->calendars->isConnectedFor($page->host())) {
            throw BookingUnavailable::refused();
        }

        return $page;
    }
}
