<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RecordContactFacts;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Keeps how a contact is reached, which sets its automatic tags (ADR 0141). A fact published before its reach was
 * tells none: the rebuild reads it again.
 */
final readonly class RecordContactFactsHandler implements EventHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(ContactFactsChanged $event): void
    {
        if (null === $event->hasEmail || null === $event->hasPhone || null === $event->hasMobile || null === $event->hasLinkedIn) {
            return;
        }

        $this->recorder->record(new ContactFact(
            new AccountId($event->accountId),
            new PersonId($event->personId),
            $event->revision,
            $event->present,
            new Reach($event->hasEmail, $event->hasPhone, $event->hasMobile, $event->hasLinkedIn),
        ));
    }
}
