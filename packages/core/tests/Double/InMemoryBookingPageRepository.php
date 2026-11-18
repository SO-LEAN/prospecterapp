<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The DynamoDB repository in the scenarios: the booking page of each host, found by the code it has now.
 */
final readonly class InMemoryBookingPageRepository implements BookingPageRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(MemberId $host): BookingPage
    {
        return $this->database->bookingPage($host->value) ?? throw UnknownBookingPage::of($host);
    }

    public function find(MemberId $host): ?BookingPage
    {
        return $this->database->bookingPage($host->value);
    }

    public function save(BookingPage $page): void
    {
        $this->database->storeBookingPage($page);
    }
}
