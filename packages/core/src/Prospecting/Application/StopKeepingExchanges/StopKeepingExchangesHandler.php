<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingExchanges;

use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * An organization no longer prospected takes its exchanges along: those kept for no other organization are
 * erased, with the personal data their text may hold (ADR 0074).
 */
final readonly class StopKeepingExchangesHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private Events $events,
    ) {
    }

    public function __invoke(ProspectingStopped $event): void
    {
        $prospector = $this->prospectorService->prospectorFrom($event->by);

        foreach ($this->exchanges->keptFor($event->organization) as $exchange) {
            $exchange->stopKeepingFor($event->organization, by: $prospector, at: $event->occurredAt);
            $this->keepOrErase($exchange);
            $this->publishWhatHappenedTo($exchange);
        }
    }

    private function keepOrErase(Exchange $exchange): void
    {
        if ($exchange->isKeptForAnOrganization()) {
            $this->exchanges->save($exchange);
        } else {
            $this->exchanges->remove($exchange);
        }
    }
}
