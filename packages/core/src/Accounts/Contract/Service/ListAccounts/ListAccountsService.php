<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ListAccounts;

/**
 * The service that Accounts offers the other contexts (ADR 0045, 0089). It refuses nothing.
 */
interface ListAccountsService
{
    public function __invoke(ListAccounts $query, ListAccountsOutput $output): void;
}
