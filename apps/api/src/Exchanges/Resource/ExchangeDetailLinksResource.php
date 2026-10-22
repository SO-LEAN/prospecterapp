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
        #[OA\Property(nullable: false, description: 'Its text only, offered without the right to manage personal data too')]
        public ?LinkResource $correctText = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
        #[OA\Property(nullable: false, description: 'Joins a file sent with the form of an upload, while the exchange has room for one')]
        public ?LinkResource $attachFile = null,
    ) {
    }
}
