<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Attachment\DiscardAttachmentsOfErasedExchange\DiscardAttachmentsOfErasedExchange;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange is erased, its files leave the private storage, since they may hold personal data (ADR 0077,
 * 0101).
 */
final readonly class DiscardAttachmentsOfErasedExchangePolicy implements Policy
{
    public function __invoke(ExchangeErased $event): DiscardAttachmentsOfErasedExchange
    {
        return new DiscardAttachmentsOfErasedExchange($event->files, $event->occurredAt);
    }
}
