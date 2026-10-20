<?php

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeDetailLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $contact,
        #[OA\Property(nullable: false)]
        public ?LinkResource $correctExchange = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
    ) {
    }
}
