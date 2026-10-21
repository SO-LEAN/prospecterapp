<?php

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeDetailLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'The card of the interlocutor: the contact, or the organization itself')]
        public LinkResource $interlocutor,
        #[OA\Property(nullable: false)]
        public ?LinkResource $correctExchange = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
    ) {
    }
}
