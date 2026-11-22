<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Resource\ExchangeSyncResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultExchangeSync\ConsultExchangeSync;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Where the push of an exchange into my calendar stands, as the API shows it; a controller calls it after a command,
 * which returns nothing, to answer with the fresh resource.
 */
final readonly class ExchangeSyncReader
{
    public function __construct(
        private QueryHandler $consultExchangeSync,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $me, string $exchangeId): ExchangeSyncResource
    {
        $presenter = new ExchangeSyncResourcePresenter($this->links, $exchangeId);
        $this->consultExchangeSync->__invoke(new ConsultExchangeSync($me, $exchangeId, $this->clock->now()), $presenter);

        return $presenter->resource;
    }
}
