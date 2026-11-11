<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Policy;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;

/**
 * What the figures of an account gain or lose when a fact takes the place of the one known of its aggregate.
 */
final class FiguresDelta
{
    #[\NoDiscard]
    public static function between(?Fact $known, Fact $received): FiguresChange
    {
        return $received->contribution()->minus($known?->contribution() ?? FiguresChange::none());
    }
}
