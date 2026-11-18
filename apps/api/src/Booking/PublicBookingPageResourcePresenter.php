<?php

namespace App\Booking;

use App\Booking\Resource\PublicBookingPageResource;
use App\Booking\Resource\PublicHostResource;
use App\Shared\ImageResourceFactory;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPageOutput;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\Booking\Application\PublicHost;

/**
 * Presents a booking page as anyone holding its link reads it, an object of the call (ADR 0085).
 */
final class PublicBookingPageResourcePresenter implements ConsultPublicBookingPageOutput
{
    public private(set) PublicBookingPageResource $resource;

    public function __construct(private readonly ImageResourceFactory $imageResourceFactory)
    {
    }

    public function present(PublicBookingPage $page): void
    {
        $this->resource = new PublicBookingPageResource($page->open, null === $page->host ? null : $this->hostOf($page->host), $page->channels, $page->timeZone, $page->days);
    }

    private function hostOf(PublicHost $host): PublicHostResource
    {
        return new PublicHostResource($host->firstName, $host->lastName, $this->imageResourceFactory->create($host->pictureUrl), $host->organization);
    }
}
