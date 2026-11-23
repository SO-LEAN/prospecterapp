<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepCreatedVideoCallLink;

use Prospecter\Core\Connectors\Contract\Event\VideoCallLinkCreated;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A video call keeps the link that its holder's calendar created for it (ADR 0137). When the exchange was erased
 * meanwhile, the reaction has nothing left to do. When the event is delivered again, the exchange no longer requests
 * a link.
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
