<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;

/**
 * An exchange was planned, noted, moved or corrected: it happens at a moment, maybe a new meeting of its contact
 * to attach (ADR 0146).
 */
interface SetsTheMomentOfAnExchange
{
    public \DateTimeImmutable $occurredAt { get; }

    public function subject(): ExchangeId;
}
