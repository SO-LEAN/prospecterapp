<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishExchangeFacts;

use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeEvent;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts an exchange's facts each time something happens to it. It reads them as the exchange's
 * service does, before the commit: the unit of work gives them when the command is about to write them.
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
            $facts->erasedWithItsOrganization,
        ));
    }

    /**
     * An erased exchange is no longer read. Its last facts name the contact and the campaign that its erasure tells
     * (ADR 0152). They also say whether it went with its organization (ADR 0155).
     */
    private function factsOf(ExchangeEvent $event): ExchangeFactsResponse
    {
        return $event instanceof ExchangeErased
            ? ExchangeFactsResponse::absent((string) $event->exchange, $event->contact?->value, $event->campaign?->value, $event->withItsOrganization)
            : $this->exchangeFactsRetriever->of($event->exchange);
    }
}
