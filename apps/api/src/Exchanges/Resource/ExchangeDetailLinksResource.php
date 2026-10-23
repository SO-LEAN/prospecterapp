<?php

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeDetailLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The card of the interlocutor: the contact, or the organization itself; absent for a forgotten contact')]
        public ?LinkResource $interlocutor = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $correctExchange = null,
        #[OA\Property(nullable: false, description: 'Its text only, offered without the right to manage personal data too')]
        public ?LinkResource $correctText = null,
        #[OA\Property(nullable: false, description: 'Its contact forgotten, a correction of its facts or of its text with madeConform declares it made conform, until it is; offered without the right to manage personal data too')]
        public ?LinkResource $makeConform = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
        #[OA\Property(nullable: false, description: 'Joins a file sent with the form of an upload, while the exchange has room for one')]
        public ?LinkResource $attachFile = null,
    ) {
    }
}
