<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the accounts of Prospecter, whose tags are derived and among which a campaign is found by its identity alone,
 * as Accounts lists them through its published service `ListAccountsService` (ADR 0045, 0089).
 */
interface TaggedAccounts
{
    /**
     * @return iterable<AccountId>
     */
    public function all(): iterable;
}
