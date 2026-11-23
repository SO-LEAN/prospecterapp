<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;

/**
 * The state of another context's aggregate that Campaigns derives tags from, with its revision (ADR 0099, 0141). A
 * terminal fact, absent, ends the aggregate.
 */
interface Fact
{
    public AccountId $accountId { get; }

    public Uuid $id { get; }

    public int $revision { get; }

    public bool $present { get; }
}
