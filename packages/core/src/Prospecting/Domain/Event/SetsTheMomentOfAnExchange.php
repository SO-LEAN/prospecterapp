<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;

/**
 * An exchange was planned, logged, moved or corrected. It happens at a moment, which may be a new meeting with its
 * contact to attach (ADR 0146).
 */
interface SetsTheMomentOfAnExchange
{
    public \DateTimeImmutable $occurredAt { get; }

    public function subject(): ExchangeId;
}
