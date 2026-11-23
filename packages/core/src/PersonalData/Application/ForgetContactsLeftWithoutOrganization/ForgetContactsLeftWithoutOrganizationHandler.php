<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Contract\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact is never alone (ADR 0042). So the handler erases the persons that no organization names any more. The
 * member whose gesture left them alone erases them. The handler reacts to the integration event Prospecting
 * publishes, translated into PersonalData's words (ADR 0046).
 */
final readonly class ForgetContactsLeftWithoutOrganizationHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ContactsLeftWithoutOrganization $event): void
    {
        $reader = $this->readers->readerFrom(new MemberId($event->memberId));
        $alone = $this->persons->ofIds(array_map(static fn (string $contact): PersonId => new PersonId($contact), $event->contactIds));

        foreach ($alone as $contact) {
            $contact->forget(by: $reader, at: $event->occurredAt);
        }

        foreach ($alone as $contact) {
            $this->persons->remove($contact);
            $this->publishWhatHappenedTo($contact);
        }
    }
}
