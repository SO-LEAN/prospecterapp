<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Resource\ExchangeSyncLinksResource;
use App\Connectors\Resource\ExchangeSyncResource;
use App\Connectors\Resource\SyncResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\Calendar\ConsultExchangeSync\ConsultExchangeSyncOutput;
use Prospecter\Core\Connectors\Application\Calendar\ExchangeSync;

/**
 * Presents an exchange's push state as the API shows it. It is built for each call (ADR 0085). The exchange can be
 * pushed again only once the provider refused it.
 */
final class ExchangeSyncResourcePresenter implements ConsultExchangeSyncOutput
{
    private const string FAILED = 'failed';

    public private(set) ExchangeSyncResource $resource;

    public function __construct(private readonly Links $links, private readonly string $exchangeId)
    {
    }

    public function present(?ExchangeSync $sync): void
    {
        $this->resource = new ExchangeSyncResource(
            null === $sync ? null : new SyncResource($sync->provider, $sync->state, $sync->at, $sync->reason),
            new ExchangeSyncLinksResource(
                $this->links->to('api_exchanges_google_sync', ['id' => $this->exchangeId]),
                self::FAILED === $sync?->state ? $this->links->to('api_exchanges_google_sync_retry', ['id' => $this->exchangeId]) : null,
            ),
        );
    }
}
