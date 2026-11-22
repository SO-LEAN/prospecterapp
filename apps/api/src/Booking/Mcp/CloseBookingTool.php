<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\CloseBooking\CloseBooking;

/**
 * `DELETE /api/me/booking-page/opening`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_booking_page_close')]
final readonly class CloseBookingTool
{
    public function __construct(private BookingPageWrites $writes)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'close-booking', description: 'Close the booking page of the member: nobody books them at its public link any more. Answers the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    public function __invoke(): array
    {
        return $this->writes->change(
            static fn (string $memberId, \DateTimeImmutable $now): CloseBooking => new CloseBooking(memberId: $memberId, executedAt: $now),
        );
    }
}
