<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Policy;

use Prospecter\Core\Reporting\Domain\Model\Fact;

/**
 * Decides which of an aggregate's facts Reporting keeps (ADR 0099). The facts arrive in any order, possibly more than
 * once. The terminal fact ends the aggregate for good. Otherwise the highest revision is the latest.
 */
final class FactSuccession
{
    #[\NoDiscard]
    public static function replaces(Fact $received, ?Fact $known): bool
    {
        return match (true) {
            null === $known => true,
            !$known->present => false,
            !$received->present => true,
            default => $received->revision > $known->revision,
        };
    }
}
