<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

/**
 * The holder pushes again an exchange that the provider refused.
 */
final readonly class ExchangeSyncLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Push the exchange again. Present once the provider refused it', nullable: false)]
        #[Offers('api_exchanges_google_sync_retry')]
        public ?LinkResource $retry = null,
    ) {
    }
}
