<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

/**
 * The settings change at any time. `open` is offered while the page is closed, `close` while it is open, and
 * `changeLink` once it has a link.
 */
final readonly class BookingPageLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[Offers('api_booking_page_set_windows')]
        public LinkResource $setWindows,
        #[Offers('api_booking_page_add_inactive_period')]
        public LinkResource $addInactivePeriod,
        #[Offers('api_booking_page_offer_channels')]
        public LinkResource $offerChannels,
        #[OA\Property(description: 'Open the page. Offered only while the page is closed', nullable: false)]
        #[Offers('api_booking_page_open')]
        public ?LinkResource $open = null,
        #[OA\Property(description: 'Close the page at once. Offered only while the page is open', nullable: false)]
        #[Offers('api_booking_page_close')]
        public ?LinkResource $close = null,
        #[OA\Property(description: 'Draw a new code for the page\'s link. Offered only once the page has a link', nullable: false)]
        #[Offers('api_booking_page_change_link')]
        public ?LinkResource $changeLink = null,
    ) {
    }
}
