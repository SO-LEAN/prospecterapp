<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseExchangeLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * An exchange kept for no organization is erased, with the personal data its text and its files may hold
 * (ADR 0074, 0077), by the member who stopped prospecting its organization. Delivered again once it is erased, the
 * reaction has nothing left to do.
 */
final readonly class EraseExchangeLeftWithoutOrganizationHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private AttachedFiles $attachedFiles,
        private Events $events,
    ) {
    }

    public function __invoke(ExchangeLeftWithoutOrganization $event): void
    {
        try {
            $exchange = $this->exchanges->get($event->exchange);
        } catch (UnknownExchange) {
            return;
        }

        $exchange->erase(by: $event->by, at: $event->occurredAt);
        $this->exchanges->remove($exchange);
        $this->attachedFiles->forgetThoseOf($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
