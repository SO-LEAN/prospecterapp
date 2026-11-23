<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

interface ListAccountsOutput
{
    /**
     * @param list<string> $accountIds
     * @param string|null  $next       the next slice's cursor, null when this slice is the last
     */
    public function present(array $accountIds, ?string $next): void;
}
