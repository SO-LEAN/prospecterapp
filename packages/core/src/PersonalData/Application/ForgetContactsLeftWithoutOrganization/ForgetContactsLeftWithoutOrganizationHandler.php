<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ContactsLeftWithoutOrganization;

/**
 * A contact is never alone (ADR 0042): the persons no organization names any more are erased, by the member
 * whose gesture left them alone.
 */
final readonly class ForgetContactsLeftWithoutOrganizationHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ContactsLeftWithoutOrganization $event): void
    {
        $reader = $this->readers->get($event->by);
        $alone = $this->persons->ofIds($event->contacts);

        foreach ($alone as $contact) {
            $contact->forget(by: $reader, at: $event->occurredAt);
        }

        foreach ($alone as $contact) {
            $this->persons->remove($contact);
            $this->publishWhatHappenedTo($contact);
        }
    }
}
