<?php

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class CampaignLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Change its name and its message, its channel and its tags too while a draft', nullable: false)]
        public ?LinkResource $revise = null,
        #[OA\Property(description: 'Discard it, never started', nullable: false)]
        public ?LinkResource $discard = null,
        #[OA\Property(description: 'Keep the contacts it targets now as its recipients, while a draft', nullable: false)]
        public ?LinkResource $freeze = null,
        #[OA\Property(description: 'Add a contact chosen to its recipients, once frozen', nullable: false)]
        public ?LinkResource $addRecipient = null,
        #[OA\Property(description: 'Add the contacts of a search by tags to its recipients, once frozen', nullable: false)]
        public ?LinkResource $addRecipientsByTags = null,
        #[OA\Property(description: 'The contacts it targets while a draft: the search by its targeted tags', nullable: false)]
        public ?LinkResource $target = null,
        #[OA\Property(description: 'The contacts it keeps once frozen', nullable: false)]
        public ?LinkResource $recipients = null,
    ) {
    }
}
