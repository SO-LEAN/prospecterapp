<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingExchanges;

use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * An organization no longer prospected takes its exchanges along: those kept for no other organization are
 * erased, with the personal data their text and their files may hold (ADR 0074, 0077).
 */
final readonly class StopKeepingExchangesHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
        private AttachedFiles $attachedFiles,
    ) {
    }

    public function __invoke(ProspectingStopped $event): void
    {
        foreach ($this->exchanges->keptFor($event->organization) as $exchange) {
            $exchange->stopKeepingFor($event->organization, by: $event->by, at: $event->occurredAt);
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
            $this->attachedFiles->forgetThoseOf($exchange);
        }
    }
}
