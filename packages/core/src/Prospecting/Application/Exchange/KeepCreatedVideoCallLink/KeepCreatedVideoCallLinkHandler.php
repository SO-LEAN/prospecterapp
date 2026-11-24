<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\KeepCreatedVideoCallLink;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A video call keeps the link that its holder's calendar created for it (ADR 0137). When the exchange was erased
 * meanwhile, the command has nothing left to do. When the command comes again, the exchange no longer requests a
 * link.
 */
final readonly class KeepCreatedVideoCallLinkHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(KeepCreatedVideoCallLink $command): void
    {
        try {
            $exchange = $this->exchanges->get(new ExchangeId($command->exchangeId));
        } catch (UnknownExchange) {
            return;
        }

        $exchange->receiveLink(new VideoCallLink($command->link), at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
