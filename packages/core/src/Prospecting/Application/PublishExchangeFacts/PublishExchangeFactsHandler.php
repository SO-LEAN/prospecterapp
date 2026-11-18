<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishExchangeFacts;

use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeEvent;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts the facts of an exchange each time something happens to it, read as its service
 * reads them, before the commit: the unit of work gives them as the command is about to write them.
 */
final readonly class PublishExchangeFactsHandler implements EventHandler
{
    public function __construct(
        private ExchangeFactsRetriever $exchangeFactsRetriever,
        private Events $events,
    ) {
    }

    public function __invoke(ExchangeEvent $event): void
    {
        $facts = $this->factsOf($event);

        $this->events->publish(new ExchangeFactsChanged(
            (string) $event->accountId,
            $facts->exchangeId,
            $facts->revision,
            $facts->present,
            $facts->status,
            $facts->notedAt,
            $event->occurredAt,
            $facts->contactId,
            $facts->campaignId,
        ));
    }

    /**
     * Erased, the exchange is no longer read: its last facts name the contact and the campaign its erasure tells
     * (ADR 0152).
     */
    private function factsOf(ExchangeEvent $event): ExchangeFactsResponse
    {
        return $event instanceof ExchangeErased
            ? ExchangeFactsResponse::absent((string) $event->exchange, $event->contact?->value, $event->campaign?->value)
            : $this->exchangeFactsRetriever->of($event->exchange);
    }
}
