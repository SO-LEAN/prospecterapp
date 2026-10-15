<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\Prospecting\Contract\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact is never alone (ADR 0042): the persons no organization names any more are erased, by the member
 * whose gesture left them alone; the integration event Prospecting publishes, translated into the words of
 * PersonalData (ADR 0046).
 */
final readonly class ForgetContactsLeftWithoutOrganizationHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private PersonRepository $persons, private ReaderService $readerService, private Events $events)
    {
    }

    public function __invoke(ContactsLeftWithoutOrganization $event): void
    {
        $reader = $this->readerService->readerFrom(new MemberId($event->memberId));
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
