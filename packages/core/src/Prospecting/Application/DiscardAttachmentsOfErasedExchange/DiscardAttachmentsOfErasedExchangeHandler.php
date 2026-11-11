<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardAttachmentsOfErasedExchange;

use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;

/**
 * The files joined to an exchange go with it, since they may hold personal data (ADR 0077), once its erasure is
 * written, never before. Delivered again, it removes nothing more.
 */
final readonly class DiscardAttachmentsOfErasedExchangeHandler implements EventHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    public function __invoke(ExchangeErased $event): void
    {
        foreach ($event->files as $file) {
            $this->privateStorage->remove($file);
        }
    }
}
