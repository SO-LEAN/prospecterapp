<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Model\Fact;

/**
 * The facts of the organizations and of the contacts of each account, the tags of the contacts derive from (ADR 0141).
 */
interface FactRepository
{
    /**
     * The fact kept of the aggregate the given fact is about, of the same kind, null when none is kept.
     */
    public function find(Fact $about): ?Fact;

    /**
     * Keeps the fact in place of the one it replaces, found by find(); refused when the kept fact is no longer the
     * replaced one.
     */
    public function record(Fact $fact, ?Fact $replaced): void;
}
