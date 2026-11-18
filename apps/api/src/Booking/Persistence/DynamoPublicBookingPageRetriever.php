<?php

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Application\PublicBookingPage;

/**
 * The page a code leads to: the link of the code to its host, then the page of that host (ADR 0151).
 */
final readonly class DynamoPublicBookingPageRetriever implements PublicBookingPageRetriever
{
    public function __construct(private Table $bookingTable)
    {
    }

    public function byCode(string $code): ?PublicBookingPage
    {
        $host = $this->bookingTable->item(BookingKeys::code($code), BookingKeys::CODE)['host'] ?? null;
        $document = null === $host ? null : $this->bookingTable->item(BookingKeys::page($host), BookingKeys::PAGE)['page'] ?? null;

        return null === $document ? null : BookingPageDocument::publicPage($document);
    }
}
