<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Rule;

use Prospecter\Core\Campaigns\Domain\Model\Fact;

/**
 * Which of an aggregate's facts Campaigns keeps (ADR 0099, 0141). The facts arrive in any order, and possibly more than
 * once. The terminal fact ends the aggregate for good. Otherwise, the highest revision is the latest. A fact rebuilt
 * from its context's state also replaces the kept fact at the same revision. The kept fact may have been recorded
 * before the facts carried what they carry now.
 */
final class FactSuccession
{
    #[\NoDiscard]
    public static function replaces(Fact $received, ?Fact $known): bool
    {
        return self::succeeds($received, $known, static fn (int $received, int $known): bool => $received > $known);
    }

    #[\NoDiscard]
    public static function replacesAsRebuilt(Fact $rebuilt, ?Fact $known): bool
    {
        return self::succeeds($rebuilt, $known, static fn (int $rebuilt, int $known): bool => $rebuilt >= $known);
    }

    /**
     * @param \Closure(int, int): bool $isLater whether the received revision is later than the known one
     */
    private static function succeeds(Fact $received, ?Fact $known, \Closure $isLater): bool
    {
        return match (true) {
            null === $known => true,
            !$known->present => false,
            !$received->present => true,
            default => $isLater($received->revision, $known->revision),
        };
    }
}
