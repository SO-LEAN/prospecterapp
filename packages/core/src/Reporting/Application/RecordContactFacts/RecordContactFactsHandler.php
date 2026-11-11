<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordContactFacts;

use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Counts the contacts of an account from the facts PersonalData publishes (ADR 0099).
 */
final readonly class RecordContactFactsHandler implements EventHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(ContactFactsChanged $event): void
    {
        $this->recorder->record(new ContactFact(new AccountId($event->accountId), new PersonId($event->personId), $event->revision, $event->present));
    }
}
