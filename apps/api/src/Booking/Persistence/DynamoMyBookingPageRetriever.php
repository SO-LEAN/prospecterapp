<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\Booking\Application\Port\MyBookingPageRetriever;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads a host's booking page from its item, without restoring it (ADR 0088). A page that was never set up is closed
 * and empty.
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
