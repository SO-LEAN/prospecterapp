<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\BookingCodes;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\ChangeBookingLink\ChangeBookingLink;

/**
 * `POST /api/me/booking-page/link`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page_change_link')]
final readonly class ChangeBookingLinkTool
{
    public function __construct(private BookingPageWrites $writes, private BookingCodes $codes)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-booking-link', description: 'Give the member\'s booking page a new public link. The former link leads nowhere any more. Returns the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(): array
    {
        return $this->writes->change(
            fn (string $memberId, \DateTimeImmutable $now): ChangeBookingLink => new ChangeBookingLink(memberId: $memberId, drawnCode: $this->codes->draw(), executedAt: $now),
        );
    }
}
