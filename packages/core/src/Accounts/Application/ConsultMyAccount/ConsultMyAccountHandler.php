<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyAccount;

use Prospecter\Core\Accounts\Application\Port\MyAccountRetriever;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyAccountHandler implements QueryHandler
{
    public function __construct(private MyAccountRetriever $myAccountRetriever)
    {
    }

    public function __invoke(ConsultMyAccount $query, ConsultMyAccountOutput $output): void
    {
        $output->present($this->myAccountRetriever->of(new MemberId($query->memberId)));
    }
}
