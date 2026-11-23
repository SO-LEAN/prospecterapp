<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyAccountClients;

use Prospecter\Core\Connectors\Application\Port\AccountClientsRetriever;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The clients of the account that the member administers, without their secrets.
 */
final readonly class ConsultMyAccountClientsHandler implements QueryHandler
{
    public function __construct(
        private AccountClientsRetriever $accountClientsRetriever,
        private Members $members,
    ) {
    }

    public function __invoke(ConsultMyAccountClients $query, ConsultMyAccountClientsOutput $output): void
    {
        $output->present($this->accountClientsRetriever->of($this->members->get(new MemberId($query->memberId))->administeredAccount()));
    }
}
