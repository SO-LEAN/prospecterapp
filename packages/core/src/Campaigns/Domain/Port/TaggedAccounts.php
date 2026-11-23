<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: Prospecter's accounts, as Accounts lists them through its published service `ListAccountsService` (ADR 0045,
 * 0089). Their tags are derived, and a campaign is found among them by its id alone.
 */
interface TaggedAccounts
{
    /**
     * @return iterable<AccountId>
     */
    public function all(): iterable;
}
