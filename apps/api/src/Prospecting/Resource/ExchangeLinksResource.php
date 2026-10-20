<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The whole of the exchange, who noted it and who corrected it')]
        public LinkResource $self,
    ) {
    }
}
