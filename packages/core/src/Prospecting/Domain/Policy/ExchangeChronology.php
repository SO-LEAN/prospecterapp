<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * Where an exchange stands in a timeline: at its moment, but a planned exchange cancelled before it, listed among
 * the past from its cancellation, still showing its moment (ADR 0112).
 */
final class ExchangeChronology
{
    #[\NoDiscard]
    public static function listedAt(\DateTimeImmutable $occurredAt, ?\DateTimeImmutable $cancelledAt): \DateTimeImmutable
    {
        return null !== $cancelledAt && $cancelledAt < $occurredAt ? $cancelledAt : $occurredAt;
    }
}
