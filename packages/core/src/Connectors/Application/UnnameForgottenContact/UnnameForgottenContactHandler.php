<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\UnnameForgottenContact;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A forgotten contact leaves the events of their done or missed exchanges (ADR 0127). Each event is put again without
 * their name, and it leads to its organization. Prospecting cancels the upcoming exchanges, and then they are
 * withdrawn.
 */
final readonly class UnnameForgottenContactHandler implements EventHandler
{
    public function __construct(
        private HeldExchanges $exchanges,
        private CalendarEntryRepository $entries,
        private CalendarPusher $pusher,
    ) {
    }

    public function __invoke(ContactForgotten $forgotten): void
    {
        $accountId = new AccountId($forgotten->accountId);

        foreach ($this->entries->ofContact(new PersonId($forgotten->personId)) as $entry) {
            $this->unname($entry, $accountId, $forgotten->occurredAt);
        }
    }

    private function unname(CalendarEntry $entry, AccountId $accountId, \DateTimeImmutable $at): void
    {
        $exchange = $this->exchanges->find($accountId, $entry->exchange());
        if (null !== $exchange && $exchange->staysInTheCalendar()) {
            $this->pusher->push($exchange, $at);
        }
    }
}
