<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\Port\AccountClientsRetriever;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * My connections, with my exchanges to come synced and failed there, and the providers offered to me: those a client
 * of my account serves.
 */
final readonly class ConsultMyConnectionsHandler implements QueryHandler
{
    public function __construct(
        private MyConnectionsRetriever $myConnectionsRetriever,
        private AccountClientsRetriever $accountClientsRetriever,
        private Members $members,
    ) {
    }

    public function __invoke(ConsultMyConnections $query, ConsultMyConnectionsOutput $output): void
    {
        $me = new MemberId($query->memberId);

        $output->present($this->myConnectionsRetriever->of($me, $query->readAt), $this->accountClientsRetriever->of($this->members->get($me)->accountId));
    }
}
