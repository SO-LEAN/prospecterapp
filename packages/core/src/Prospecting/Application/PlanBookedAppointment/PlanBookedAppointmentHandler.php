<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanBookedAppointment;

use Prospecter\Core\PersonalData\Contract\Event\BookerIdentified;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\BookedAppointments;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Someone books an appointment through a host's public link. The appointment becomes an exchange that the host
 * planned with the booker, under the appointment's id (ADR 0164). The booker is a contact of the account. When nobody
 * knew them, they become a contact to attach, held by the host who meets them (ADR 0146). When the appointment is gone
 * meanwhile, there is nothing to do.
 */
final readonly class PlanBookedAppointmentHandler implements EventHandler
{
    use PublishesEvents;

    /**
     * What the host plans with a contact to attach is kept for no organization. The host keeps it (ADR 0146).
     */
    private const array NO_KEEPER_OF_A_CONTACT_TO_ATTACH = [];

    public function __construct(
        private ExchangeRepository $exchanges,
        private ContactToAttachRepository $contactsToAttach,
        private ContactOrganizations $contactOrganizations,
        private BookedAppointments $bookedAppointments,
        private Prospectors $prospectors,
        private Events $events,
    ) {
    }

    public function __invoke(BookerIdentified $event): void
    {
        $appointment = $this->bookedAppointments->bookedAppointment($event->appointmentId);
        if (null === $appointment) {
            return;
        }

        $host = $this->prospectors->prospectorFrom(new MemberId($event->hostId));
        $contact = new PersonId($event->personId);
        $toAttach = $this->contactsToAttach->find($contact);
        $known = $this->contactOrganizations->isAContactOfTheAccount($contact, $host->accountId);
        if (!$known) {
            $this->meetToAttach($host, $contact, $appointment->startsAt, $event->occurredAt);
        } elseif (null !== $toAttach && !$toAttach->isHeldBy($host->id)) {
            $this->handOver($toAttach, $host, $appointment->startsAt, $event->occurredAt);
        }
        $keepers = $known && null === $toAttach ? $this->contactOrganizations->keepersOf($contact, $host) : self::NO_KEEPER_OF_A_CONTACT_TO_ATTACH;
        $exchange = Exchange::bookedByAContact(new Gesture($host->accountId, $host->id, $event->occurredAt), new ExchangeId($event->appointmentId), new ContactCorrespondent($contact, $keepers), $appointment->facts());
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    private function handOver(ContactToAttach $toAttach, Prospector $host, \DateTimeImmutable $meeting, \DateTimeImmutable $at): void
    {
        $toAttach->handOverTo($host, $meeting, $at);
        $this->contactsToAttach->save($toAttach);
        $this->publishWhatHappenedTo($toAttach);
    }

    private function meetToAttach(Prospector $host, PersonId $contact, \DateTimeImmutable $meeting, \DateTimeImmutable $at): void
    {
        $toAttach = ContactToAttach::metBy($host, $contact, $meeting, $at);
        $this->contactsToAttach->save($toAttach);
        $this->publishWhatHappenedTo($toAttach);
    }
}
