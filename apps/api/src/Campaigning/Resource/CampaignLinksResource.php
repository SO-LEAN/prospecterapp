<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class CampaignLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Change its name and message, and also its channel and tags while it is a draft', nullable: false)]
        public ?LinkResource $revise = null,
        #[OA\Property(description: 'Discard it, before it ever starts', nullable: false)]
        public ?LinkResource $discard = null,
        #[OA\Property(description: 'Freeze a draft: the contacts it targets now become its recipients', nullable: false)]
        public ?LinkResource $freeze = null,
        #[OA\Property(description: 'Add a chosen contact to its recipients, once it is frozen', nullable: false)]
        public ?LinkResource $addRecipient = null,
        #[OA\Property(description: 'Add the contacts that a tag search finds to its recipients, once it is frozen', nullable: false)]
        public ?LinkResource $addRecipientsByTags = null,
        #[OA\Property(description: 'Start it, once it is frozen. Nothing in it changes any more', nullable: false)]
        public ?LinkResource $start = null,
        #[OA\Property(description: 'Stop it, once it has started. It is kept, never discarded', nullable: false)]
        public ?LinkResource $stop = null,
        #[OA\Property(description: 'Set its daily cap, or take it off, until it is stopped or ended', nullable: false)]
        public ?LinkResource $setDailyCap = null,
        #[OA\Property(description: 'The contacts it targets while it is a draft: the search by its targeted tags', nullable: false)]
        public ?LinkResource $target = null,
        #[OA\Property(description: 'The contacts it keeps once it is frozen, started or stopped', nullable: false)]
        public ?LinkResource $recipients = null,
    ) {
    }
}
