<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The state of an aggregate of another context, as Reporting counts it (ADR 0099): the highest revision is the
 * latest, and an absent fact, the terminal one, ends the aggregate.
 */
interface Fact
{
    public AccountId $accountId { get; }

    public Uuid $id { get; }

    public int $revision { get; }

    public bool $present { get; }

    /**
     * What the fact adds to the figures of its account, counted from nothing.
     */
    public function contribution(): FiguresChange;
}
