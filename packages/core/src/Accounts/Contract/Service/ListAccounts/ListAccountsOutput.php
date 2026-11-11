<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

interface ListAccountsOutput
{
    /**
     * @param list<string> $accountIds the identities of the accounts
     * @param string|null  $next       the cursor of the next slice, null when this one is the last
     */
    public function present(array $accountIds, ?string $next): void;
}
