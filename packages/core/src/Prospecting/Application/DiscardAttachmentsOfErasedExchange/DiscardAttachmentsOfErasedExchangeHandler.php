<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardAttachmentsOfErasedExchange;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

/**
 * Removes an erased exchange's files, since they may hold personal data (ADR 0077). It removes them once the
 * erasure is written, never before. When the event is delivered again, it removes nothing more.
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
