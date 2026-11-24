<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * The settings change at any time. `open` is offered while the page is closed, `close` while it is open, and
 * `changeLink` once it has a link.
 */
final readonly class BookingPageLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $setWindows,
        public LinkResource $addInactivePeriod,
        public LinkResource $offerChannels,
        #[OA\Property(description: 'Open the page. Offered only while the page is closed', nullable: false)]
        public ?LinkResource $open = null,
        #[OA\Property(description: 'Close the page at once. Offered only while the page is open', nullable: false)]
        public ?LinkResource $close = null,
        #[OA\Property(description: 'Draw a new code for the page\'s link. Offered only once the page has a link', nullable: false)]
        public ?LinkResource $changeLink = null,
    ) {
    }
}
