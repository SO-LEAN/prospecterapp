<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The state of an aggregate of another context as Campaigns derives the tags from it, with its revision (ADR 0099,
 * 0141); a terminal fact, absent, ends the aggregate.
 */
interface Fact
{
    public AccountId $accountId { get; }

    public Uuid $id { get; }

    public int $revision { get; }

    public bool $present { get; }
}
