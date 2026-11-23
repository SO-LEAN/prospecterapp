<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;

/**
 * The state of another context's aggregate, as Reporting counts it (ADR 0099). The highest revision is the latest. An
 * absent fact is the terminal one: it ends the aggregate.
 */
interface Fact
{
    public AccountId $accountId { get; }

    public Uuid $id { get; }

    public int $revision { get; }

    public bool $present { get; }

    /**
     * Returns what the fact adds to its account's figures, counted from zero.
     */
    public function contribution(): FiguresChange;
}
