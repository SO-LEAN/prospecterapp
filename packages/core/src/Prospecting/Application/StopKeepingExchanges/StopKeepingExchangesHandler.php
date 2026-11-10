<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingExchanges;

use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * An organization no longer prospected takes its exchanges along: those still kept for another organization
 * are saved, each of the others left to its own reaction, that erases it (EraseExchangeLeftWithoutOrganization,
 * ADR 0074): one transaction holds a bounded number of writes.
 */
final readonly class StopKeepingExchangesHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(ProspectingStopped $event): void
    {
        foreach ($this->exchanges->keptFor($event->organization) as $exchange) {
            $exchange->stopKeepingFor($event->organization, by: $event->by, at: $event->occurredAt);

            if ($exchange->isKeptForAnOrganization()) {
                $this->exchanges->save($exchange);
            }

            $this->publishWhatHappenedTo($exchange);
        }
    }
}
