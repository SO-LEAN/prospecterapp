<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseExchangeLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * An exchange kept for no organization is erased, with the personal data its text and its files may hold
 * (ADR 0074, 0077). Delivered again once it is erased, the reaction has nothing left to do.
 */
final readonly class EraseExchangeLeftWithoutOrganizationHandler implements EventHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private AttachedFiles $attachedFiles,
    ) {
    }

    public function __invoke(ExchangeLeftWithoutOrganization $event): void
    {
        try {
            $exchange = $this->exchanges->get($event->exchange);
        } catch (UnknownExchange) {
            return;
        }

        $this->exchanges->remove($exchange);
        $this->attachedFiles->forgetThoseOf($exchange);
    }
}
