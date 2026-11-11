<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Policy;

use Prospecter\Core\Reporting\Domain\Model\Fact;

/**
 * Which fact of an aggregate Reporting keeps, the facts arriving in any order and maybe more than once (ADR 0099):
 * the terminal fact ends the aggregate for good, otherwise the highest revision is the latest.
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
