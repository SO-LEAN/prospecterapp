<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyBooker;

use Prospecter\Core\Booking\Contract\Event\SlotBooked;
use Prospecter\Core\PersonalData\Domain\Model\Booker;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Port\Bookers;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who booked a slot of a host (ADR 0164): the contact of the account having their e-mail, otherwise one having their
 * phone number, otherwise a contact to attach collected for the host, as ADR 0146 has it. An appointment gone
 * meanwhile leaves nothing to do.
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
        $contact = $this->knownContact(new AccountId($event->accountId), $booker) ?? $this->collect($booker, $host, new PersonId($event->bookerId), $event->occurredAt);
        $contact->identifyAsBookerOf($event->appointmentId, $host, $event->occurredAt);
        $this->publishWhatHappenedTo($contact);
    }

    private function knownContact(AccountId $accountId, Booker $booker): ?Person
    {
        return $this->persons->contactWithEmail($accountId, $booker->email) ?? $this->persons->contactWithPhoneNumber($accountId, $booker->phoneNumber);
    }

    private function collect(Booker $booker, MemberId $host, PersonId $id, \DateTimeImmutable $at): Person
    {
        $contact = Person::collectToAttach($this->readers->readerFrom($host), $id, $booker->name, $booker->contactDetails(), $at);
        $this->persons->save($contact);

        return $contact;
    }
}
