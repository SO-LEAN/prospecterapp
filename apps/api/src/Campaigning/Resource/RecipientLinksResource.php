<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class RecipientLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Remove them from the campaign, while it is frozen', nullable: false)]
        public ?LinkResource $remove = null,
        #[OA\Property(description: 'Log the exchange held with them, while the campaign is started and they are to handle', nullable: false)]
        public ?LinkResource $noteExchange = null,
        #[OA\Property(description: 'Dismiss them without an exchange, while the campaign is started and they are to handle', nullable: false)]
        public ?LinkResource $dismiss = null,
        #[OA\Property(description: 'The exchange logged for them, once it has processed them', nullable: false)]
        public ?LinkResource $exchange = null,
    ) {
    }
}
