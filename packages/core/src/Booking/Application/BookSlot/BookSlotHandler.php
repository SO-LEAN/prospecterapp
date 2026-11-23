<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookSlot;

use Prospecter\Core\Booking\Domain\Exception\BookingUnavailable;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Model\AppointmentRequest;
use Prospecter\Core\Booking\Domain\Model\Booker;
use Prospecter\Core\Booking\Domain\Model\BookerId;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostAgenda;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\Booking\Domain\Service\BusyPeriods;
use Prospecter\Core\Booking\Domain\Service\LinkAvailability;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

final readonly class BookSlotHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPageRepository $pages,
        private AppointmentRepository $appointments,
        private LinkAvailability $links,
        private BusyPeriods $busyPeriods,
        private Events $events,
    ) {
    }

    public function __invoke(BookSlot $command): void
    {
        $page = $this->bookablePageAt(new BookingCode($command->code));
        $appointment = $page->book(self::requestOf($command), $this->agendaOf($page, $command->executedAt), $command->executedAt);
        $this->appointments->save($appointment);
        $this->publishWhatHappenedTo($appointment);
    }

    private static function requestOf(BookSlot $command): AppointmentRequest
    {
        return new AppointmentRequest(
            new AppointmentId($command->appointmentId),
            new BookerId($command->bookerId),
            $command->slot,
            Channel::from($command->channel),
            Booker::named($command->firstName, $command->lastName, $command->phone, $command->email),
            $command->subject,
        );
    }

    private function agendaOf(BookingPage $page, \DateTimeImmutable $at): HostAgenda
    {
        return new HostAgenda($this->busyPeriods->of($page->accountId(), $page->host(), $at), $this->appointments->toComeOf($page->host(), $at));
    }

    /**
     * @throws BookingUnavailable
     */
    private function bookablePageAt(BookingCode $code): BookingPage
    {
        $page = $this->pages->findByCode($code);
        if (null === $page || !$this->links->offersSlots($page->isOpen(), $page->host())) {
            throw BookingUnavailable::refused();
        }

        return $page;
    }
}
