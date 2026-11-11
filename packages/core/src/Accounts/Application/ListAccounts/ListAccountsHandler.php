<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ListAccounts;

use Prospecter\Core\Accounts\Application\Port\AccountListRetriever;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccountsOutput;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccountsService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

final readonly class ListAccountsHandler implements QueryHandler, ListAccountsService
{
    public function __construct(private AccountListRetriever $accountListRetriever)
    {
    }

    public function __invoke(ListAccounts $query, ListAccountsOutput $output): void
    {
        $slice = $this->accountListRetriever->sliceOf($query->after, $query->max);

        $output->present($slice->content, $slice->next);
    }
}
