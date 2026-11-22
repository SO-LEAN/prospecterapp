<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * The holder pushes again an exchange the provider refused.
 */
final readonly class ExchangeSyncLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Push the exchange again, while the provider refused it', nullable: false)]
        public ?LinkResource $retry = null,
    ) {
    }
}
