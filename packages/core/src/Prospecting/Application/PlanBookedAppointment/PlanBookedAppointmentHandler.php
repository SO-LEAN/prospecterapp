<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanBookedAppointment;

use Prospecter\Core\PersonalData\Contract\Event\BookerIdentified;
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
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An appointment booked by the public link of a host becomes an exchange they planned with its booker, under the
 * identity of the appointment (ADR 0164): a contact of the account, or a contact to attach held by the host, met
 * then, when nobody knew them (ADR 0146). An appointment gone meanwhile leaves nothing to do.
 */
final readonly class PlanBookedAppointmentHandler implements EventHandler
{
    use PublishesEvents;

    /**
     * The host keeps what they plan with a contact to attach (ADR 0146).
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
        $exchange = Exchange::bookedByAContact($host->accountId, $host->id, new ExchangeId($event->appointmentId), $contact, $keepers, $appointment->facts(), $event->occurredAt);
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
