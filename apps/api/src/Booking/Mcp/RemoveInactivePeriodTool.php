<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\RemoveInactivePeriod\RemoveInactivePeriod;

/**
 * `DELETE /api/me/booking-page/inactive-periods/{id}`, for an AI (ADR 0163). The AI asks its member before it calls the tool.
 */
#[Adapts('api_booking_page_remove_inactive_period')]
final readonly class RemoveInactivePeriodTool
{
    public function __construct(private BookingPageWrites $writes)
    {
    }

    /**
     * @param string $id the inactive period's id, as the booking page lists it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'remove-inactive-period', description: 'Remove an inactive period from the member\'s booking page. Bookers can book them on those days again. Returns the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            static fn (string $memberId, \DateTimeImmutable $now): RemoveInactivePeriod => new RemoveInactivePeriod(memberId: $memberId, periodId: $id, executedAt: $now),
        );
    }
}
