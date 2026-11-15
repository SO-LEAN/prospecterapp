<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\SharedKernel\Domain\AccountId;

interface AccountClientsRetriever
{
    /**
     * @return list<AccountClient>
     */
    public function of(AccountId $account): array;
}
