<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyConnectionsHandler implements QueryHandler
{
    public function __construct(private MyConnectionsRetriever $myConnectionsRetriever)
    {
    }

    public function __invoke(ConsultMyConnections $query, ConsultMyConnectionsOutput $output): void
    {
        $output->present($this->myConnectionsRetriever->of(new MemberId($query->memberId)));
    }
}
