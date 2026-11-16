<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepCreatedVideoCallLink;

use Prospecter\Core\Connectors\Contract\Event\VideoCallLinkCreated;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * A video call keeps the link the calendar of its holder created for it (ADR 0137). Erased meanwhile, the reaction
 * has nothing left to do; delivered again, the exchange no longer asks one.
 */
final readonly class KeepCreatedVideoCallLinkHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(VideoCallLinkCreated $event): void
    {
        try {
            $exchange = $this->exchanges->get(new ExchangeId($event->exchangeId));
        } catch (UnknownExchange) {
            return;
        }

        $exchange->receiveLink(new VideoCallLink($event->link), at: $event->occurredAt);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
