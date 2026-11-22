<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\Booking\Application\Port\MyBookingPageRetriever;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The booking page of a host read from its item, without restoring it (ADR 0088); closed and empty when never set.
 */
final readonly class DynamoMyBookingPageRetriever implements MyBookingPageRetriever
{
    public function __construct(private Table $bookingTable)
    {
    }

    public function of(MemberId $host, string $today, bool $calendarConnected): MyBookingPage
    {
        $document = $this->bookingTable->item(BookingKeys::page($host->value), BookingKeys::PAGE)['page'] ?? null;

        return null === $document ? BookingPageDocument::none($calendarConnected) : BookingPageDocument::myPage($document, $today, $calendarConnected);
    }
}
