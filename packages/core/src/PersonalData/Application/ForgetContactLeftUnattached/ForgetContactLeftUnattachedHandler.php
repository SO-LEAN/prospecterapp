<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactLeftUnattached;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Contract\Event\ContactLeftUnattached;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases a contact that nobody attached in time (ADR 0146). The member who held the contact erases them, as this
 * member collected them. The handler reacts to the integration event Prospecting publishes, translated into
 * PersonalData's words (ADR 0046). It does nothing when the person is already gone.
 */
final readonly class ForgetContactLeftUnattachedHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private Readers $readers, private Events $events)
    {
    }

    public function __invoke(ContactLeftUnattached $event): void
    {
        $holder = $this->readers->readerFrom(new MemberId($event->memberId));

        foreach ($this->persons->ofIds([new PersonId($event->personId)]) as $contact) {
            $contact->forget(by: $holder, at: $event->occurredAt);
            $this->persons->remove($contact);
            $this->publishWhatHappenedTo($contact);
        }
    }
}
