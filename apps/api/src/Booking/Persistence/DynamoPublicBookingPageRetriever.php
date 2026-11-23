<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Application\PublicLink\ConsultPublicBookingPage\BookingSchedule;

/**
 * Finds the page that a code leads to: first the code's link to its host, then that host's page (ADR 0151).
 */
final readonly class DynamoPublicBookingPageRetriever implements PublicBookingPageRetriever
{
    public function __construct(private Table $bookingTable)
    {
    }

    public function byCode(string $code): ?BookingSchedule
    {
        $host = Typed::stringOrNull($this->bookingTable->item(BookingKeys::code($code), BookingKeys::CODE)['host'] ?? null);
        $document = null === $host ? null : $this->bookingTable->item(BookingKeys::page($host), BookingKeys::PAGE)['page'] ?? null;

        return null === $document ? null : BookingPageDocument::schedule($document);
    }
}
