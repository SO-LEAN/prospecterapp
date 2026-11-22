<?php

declare(strict_types=1);

namespace App\Booking;

use App\Booking\Resource\BookingPageResource;
use App\Shared\Links;
use Prospecter\Core\Booking\Application\ConsultMyBookingPage\ConsultMyBookingPage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * My booking page as the API shows it, on my clock; a controller calls it after a command, which returns nothing, to
 * answer with the fresh resource.
 */
final readonly class MyBookingPageReader
{
    private const string UTC = 'UTC';

    public function __construct(
        private QueryHandler $consultMyBookingPage,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $me, ?string $timeZone = null): BookingPageResource
    {
        $presenter = new BookingPageResourcePresenter($this->links);
        ($this->consultMyBookingPage)(new ConsultMyBookingPage($me, $timeZone ?? self::UTC, $this->clock->now()), $presenter);

        return $presenter->resource;
    }
}
