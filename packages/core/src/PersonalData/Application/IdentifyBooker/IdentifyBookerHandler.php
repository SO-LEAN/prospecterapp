<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyBooker;

use Prospecter\Core\Booking\Contract\Event\SlotBooked;
use Prospecter\Core\PersonalData\Domain\Model\Booker;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Bookers;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Identifies who booked a host's slot (ADR 0164, 0199). It looks for the account's contact who has their e-mail.
 * Otherwise, it collects a contact to attach for the host from what they typed, as ADR 0146 says. When the appointment
 * is gone meanwhile, there is nothing to do.
 */
final readonly class IdentifyBookerHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Bookers $bookers,
        private Readers $readers,
        private Events $events,
    ) {
    }

    /**
     * @throws PersonalDataManagersOnly when the host may not collect a contact
     */
    public function __invoke(SlotBooked $event): void
    {
        $booker = $this->bookers->bookerOf($event->appointmentId);
        if (null === $booker) {
            return;
        }

        $host = new MemberId($event->hostId);
        $contact = $this->persons->contactWithEmail(new AccountId($event->accountId), $booker->email) ?? $this->collect($booker, $host, new PersonId($event->bookerId), $event->occurredAt);
        $contact->identifyAsBookerOf($event->appointmentId, $host, $event->occurredAt);
        $this->publishWhatHappenedTo($contact);
    }

    private function collect(Booker $booker, MemberId $host, PersonId $id, \DateTimeImmutable $at): Person
    {
        $contact = Person::collectToAttach($this->readers->readerFrom($host), $id, new ContactCard($booker->name, $booker->contactDetails()), $at);
        $this->persons->save($contact);

        return $contact;
    }
}
