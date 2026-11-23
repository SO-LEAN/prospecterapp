<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\BookingCodes;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\BookingPage\OpenBooking\OpenBooking;

/**
 * `POST /api/me/booking-page/opening`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page_open')]
final readonly class OpenBookingTool
{
    public function __construct(private BookingPageWrites $writes, private BookingCodes $codes)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'open-booking', description: 'Open the member\'s booking page at its public link. Their Google calendar must be connected. Returns the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(): array
    {
        return $this->writes->change(
            fn (string $memberId, \DateTimeImmutable $now): OpenBooking => new OpenBooking(memberId: $memberId, drawnCode: $this->codes->draw(), executedAt: $now),
        );
    }
}
