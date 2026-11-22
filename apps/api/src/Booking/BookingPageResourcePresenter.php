<?php

declare(strict_types=1);

namespace App\Booking;

use App\Booking\Resource\BookingPageLinksResource;
use App\Booking\Resource\BookingPageResource;
use App\Booking\Resource\InactivePeriodLinksResource;
use App\Booking\Resource\InactivePeriodResource;
use App\Booking\Resource\WindowResource;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Booking\Application\ConsultMyBookingPage\ConsultMyBookingPageOutput;
use Prospecter\Core\Booking\Application\MyBookingPage;

/**
 * Presents my booking page as the API shows it, an object of the call (ADR 0085).
 */
final class BookingPageResourcePresenter implements ConsultMyBookingPageOutput
{
    public private(set) BookingPageResource $resource;

    public function __construct(private readonly Links $links)
    {
    }

    public function present(MyBookingPage $page): void
    {
        $this->resource = new BookingPageResource(
            $page->open,
            array_map(static fn (array $window): WindowResource => new WindowResource($window['weekday'], $window['from'], $window['to']), $page->windows),
            $page->timeZone,
            array_map(fn (array $period): InactivePeriodResource => new InactivePeriodResource($period['id'], $period['firstDay'], $period['lastDay'], new InactivePeriodLinksResource($this->links->to('api_booking_page_remove_inactive_period', ['id' => $period['id']]))), $page->inactivePeriods),
            $page->channels,
            $page->code,
            $page->calendarConnected,
            $page->suspended,
            new BookingPageLinksResource(
                $this->links->to('api_booking_page'),
                $this->links->to('api_booking_page_set_windows'),
                $this->links->to('api_booking_page_add_inactive_period'),
                $this->links->to('api_booking_page_offer_channels'),
                open: $this->offered('OpenBooking', $page, 'api_booking_page_open'),
                close: $this->offered('CloseBooking', $page, 'api_booking_page_close'),
                changeLink: $this->offered('ChangeBookingLink', $page, 'api_booking_page_change_link'),
            ),
        );
    }

    private function offered(string $intent, MyBookingPage $page, string $route): ?LinkResource
    {
        return in_array($intent, $page->possibleIntents, true) ? $this->links->to($route) : null;
    }
}
