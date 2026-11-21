<?php

namespace App\Booking\Mcp;

use App\Booking\BookingCodes;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\OpenBooking\OpenBooking;

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
    #[McpTool(name: 'ouvrir-reservation', description: 'Open the booking page of the member at its public link, once their calendar at Google is connected. Answers the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(): array
    {
        return $this->writes->write(
            null,
            fn (null $input, string $memberId, \DateTimeImmutable $now): OpenBooking => new OpenBooking(memberId: $memberId, drawnCode: $this->codes->draw(), executedAt: $now),
        );
    }
}
