<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Repository;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;

/**
 * The facts that each version of the figures keeps, and the figures they make.
 */
interface FactRepository
{
    /**
     * Returns the fact that the version keeps for the given fact's aggregate, or null when it keeps none.
     */
    public function find(int $version, Fact $about): ?Fact;

    /**
     * Keeps the fact in the version, in place of the fact that find() returned. It adds the change to the account's
     * figures in the same write. The write is refused when the version no longer keeps the replaced fact, because the
     * change was computed from it.
     */
    public function record(int $version, Fact $fact, ?Fact $replaced, FiguresChange $change): void;
}
