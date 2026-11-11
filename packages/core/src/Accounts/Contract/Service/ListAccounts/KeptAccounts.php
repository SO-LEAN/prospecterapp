<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptAccounts implements ListAccountsOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $accountIds = [];
    public private(set) ?string $next = null;

    /**
     * @param list<string> $accountIds
     */
    public function present(array $accountIds, ?string $next): void
    {
        $this->accountIds = $accountIds;
        $this->next = $next;
    }
}
