<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Rule;

/**
 * Where an exchange stands in a timeline. An exchange stands at its moment. A planned exchange cancelled before its
 * moment is listed among the past from its cancellation, and it still shows its moment (ADR 0112).
 */
final class ExchangeChronology
{
    #[\NoDiscard]
    public static function listedAt(\DateTimeImmutable $occurredAt, ?\DateTimeImmutable $cancelledAt): \DateTimeImmutable
    {
        return null !== $cancelledAt && $cancelledAt < $occurredAt ? $cancelledAt : $occurredAt;
    }
}
