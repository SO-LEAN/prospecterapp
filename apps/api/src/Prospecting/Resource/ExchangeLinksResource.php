<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The whole of the exchange, who noted it and who corrected it')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Moves it to another moment to come, for a new duration or its own; in the calendar of its holder and among the exchanges they have to process, while it is planned')]
        public ?LinkResource $move = null,
        #[OA\Property(nullable: false, description: 'Marks it done, with its report; in the day of its holder and among the exchanges they have to process, once its time has come')]
        public ?LinkResource $markDone = null,
        #[OA\Property(nullable: false, description: 'Marks it missed: the contact did not come or answer; in the day of its holder and among the exchanges they have to process, once its time has come')]
        public ?LinkResource $markMissed = null,
        #[OA\Property(nullable: false, description: 'Cancels it; among the exchanges its holder has to process, while it is planned')]
        public ?LinkResource $cancel = null,
        #[OA\Property(nullable: false, description: 'The campaign it was noted in, while it is kept; with the right to manage personal data only')]
        public ?LinkResource $campaign = null,
    ) {
    }
}
