<?php

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * The settings change at any time; `open` while the page is closed, `close` while it is open, `changeLink` once it
 * has a link.
 */
final readonly class BookingPageLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $setWindows,
        public LinkResource $addInactivePeriod,
        public LinkResource $offerChannels,
        #[OA\Property(description: 'Open the page, while it is closed', nullable: false)]
        public ?LinkResource $open = null,
        #[OA\Property(description: 'Close the page at once, while it is open', nullable: false)]
        public ?LinkResource $close = null,
        #[OA\Property(description: 'Draw another code for its link, once it has one', nullable: false)]
        public ?LinkResource $changeLink = null,
    ) {
    }
}
