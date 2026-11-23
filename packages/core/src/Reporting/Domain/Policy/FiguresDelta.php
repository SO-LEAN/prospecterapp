<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Policy;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;

/**
 * Computes what an account's figures gain or lose when a fact replaces its aggregate's known fact.
 */
final class FiguresDelta
{
    #[\NoDiscard]
    public static function between(?Fact $known, Fact $received): FiguresChange
    {
        return $received->contribution()->minus($known?->contribution() ?? FiguresChange::none());
    }
}
