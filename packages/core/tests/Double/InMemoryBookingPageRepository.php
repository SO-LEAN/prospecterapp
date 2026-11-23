<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Stands in for the DynamoDB repository in the scenarios. It keeps each host's booking page and finds it by its current
 * code.
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

    public function findByCode(BookingCode $code): ?BookingPage
    {
        return $this->database->bookingPageAt($code->value);
    }

    public function save(BookingPage $page): void
    {
        $this->database->storeBookingPage($page);
    }
}
