<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseExchangesOfContactLeftUnattached;

use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The exchanges of a contact left unattached were kept for no organization: nothing to keep them for, nothing to
 * make conform, they go with the contact (ADR 0146), their files by the reaction to their erasure.
 */
final readonly class EraseExchangesOfContactLeftUnattachedHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private Events $events)
    {
    }

    public function __invoke(ContactLeftUnattached $event): void
    {
        foreach ($this->exchanges->withContact($event->contact, $event->accountId) as $exchange) {
            $this->eraseKeptForNone($exchange, $event);
        }
    }

    private function eraseKeptForNone(Exchange $exchange, ContactLeftUnattached $event): void
    {
        if ($exchange->isKeptForAnOrganization()) {
            return;
        }

        $exchange->erase(by: $event->by, at: $event->occurredAt);
        $this->exchanges->remove($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
