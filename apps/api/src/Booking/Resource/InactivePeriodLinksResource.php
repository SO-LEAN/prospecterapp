<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The host can always remove one of their inactive periods.
 */
final readonly class InactivePeriodLinksResource
{
    public function __construct(
        #[Offers('api_booking_page_remove_inactive_period')]
        public LinkResource $remove,
    ) {
    }
}
