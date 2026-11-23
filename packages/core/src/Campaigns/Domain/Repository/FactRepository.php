<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Model\Fact;

/** The facts about each account's organizations and contacts. The contacts' tags are derived from them (ADR 0141). */
interface FactRepository
{
    /** The kept fact of the same kind about the given fact's aggregate, or null when none is kept. */
    public function find(Fact $about): ?Fact;

    /**
     * Keeps the fact in place of the one it replaces, which find() returned. It is refused when the kept fact is no
     * longer the replaced one.
     */
    public function record(Fact $fact, ?Fact $replaced): void;
}
