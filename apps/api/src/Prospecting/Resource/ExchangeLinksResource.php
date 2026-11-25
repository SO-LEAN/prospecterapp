<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class ExchangeLinksResource
{
    public function __construct(
        #[OA\Property(description: 'The whole exchange, with who logged it and who corrected it')]
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'Moves it to another moment to come, with a new duration or its own. In its holder\'s calendar and among the exchanges they have to process, while it is planned')]
        #[Offers('api_exchanges_move')]
        public ?LinkResource $move = null,
        #[OA\Property(nullable: false, description: 'Marks it done, with its report. In its holder\'s day and among the exchanges they have to process, once its time has come')]
        #[Offers('api_exchanges_mark_done')]
        public ?LinkResource $markDone = null,
        #[OA\Property(nullable: false, description: 'Marks it missed: the contact did not come or did not answer. In its holder\'s day and among the exchanges they have to process, once its time has come')]
        #[Offers('api_exchanges_mark_missed')]
        public ?LinkResource $markMissed = null,
        #[OA\Property(nullable: false, description: 'Cancels it. Among the exchanges its holder has to process, while it is planned')]
        #[Offers('api_exchanges_cancel')]
        public ?LinkResource $cancel = null,
        #[OA\Property(nullable: false, description: 'The campaign it was logged in, while Campaigns keeps it. Only with the right to manage personal data')]
        public ?LinkResource $campaign = null,
    ) {
    }
}
