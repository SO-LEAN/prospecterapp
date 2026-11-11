<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Repository;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;

/**
 * The facts each version of the figures keeps, and the figures they make.
 */
interface FactRepository
{
    /**
     * The fact the version keeps of the aggregate the given fact is about, null when it keeps none.
     */
    public function find(int $version, Fact $about): ?Fact;

    /**
     * Keeps the fact in the version in place of the one it replaces, found by find(), and adds the change to the
     * figures of its account, together; refused when the version no longer keeps the replaced fact, the change being
     * computed from it.
     */
    public function record(int $version, Fact $fact, ?Fact $replaced, FiguresChange $change): void;
}
