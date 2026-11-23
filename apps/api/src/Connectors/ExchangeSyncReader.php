<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Resource\ExchangeSyncResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultExchangeSync\ConsultExchangeSync;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Reads an exchange's push state in my calendar, as the API shows it. A command returns nothing, so a controller
 * calls this reader after the command to answer with the fresh resource.
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
