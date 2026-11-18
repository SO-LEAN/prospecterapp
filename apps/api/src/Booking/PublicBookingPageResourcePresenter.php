<?php

namespace App\Booking;

use App\Booking\Resource\PublicBookingPageResource;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPageOutput;
use Prospecter\Core\Booking\Application\PublicBookingPage;

/**
 * Presents a booking page as anyone holding its link reads it, an object of the call (ADR 0085).
 */
final class PublicBookingPageResourcePresenter implements ConsultPublicBookingPageOutput
{
    public private(set) PublicBookingPageResource $resource;

    public function present(PublicBookingPage $page): void
    {
        $this->resource = new PublicBookingPageResource($page->open, $page->channels, $page->days);
    }
}
