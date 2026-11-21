<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\Fact;

/**
 * Which fact of an aggregate Campaigns keeps, the facts arriving in any order and maybe more than once (ADR 0099,
 * 0141): the terminal fact ends the aggregate for good, otherwise the highest revision is the latest. A fact rebuilt
 * from the state of its context replaces the one kept at the same revision too, which may have been recorded before
 * the facts carried what they carry now.
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
     * @param \Closure(int, int): bool $isLater whether the revision received is later than the one known
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
