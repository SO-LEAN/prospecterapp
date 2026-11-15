<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMyConnectionsHandler implements QueryHandler
{
    public function __construct(
        private MyConnectionsRetriever $myConnectionsRetriever,
        private ProviderAccess $providerAccess,
    ) {
    }

    public function __invoke(ConsultMyConnections $query, ConsultMyConnectionsOutput $output): void
    {
        $output->present($this->myConnectionsRetriever->of(new MemberId($query->memberId)), $this->offeredProviders());
    }

    /**
     * @return list<string>
     */
    private function offeredProviders(): array
    {
        return array_map(
            static fn (Provider $provider): string => $provider->value,
            array_filter(Provider::cases(), $this->providerAccess->hasClient(...)),
        );
    }
}
