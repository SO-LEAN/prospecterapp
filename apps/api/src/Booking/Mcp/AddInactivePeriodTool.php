<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\Input\InactivePeriodInput;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\AddInactivePeriod\AddInactivePeriod;

/**
 * `POST /api/me/booking-page/inactive-periods`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page_add_inactive_period')]
final readonly class AddInactivePeriodTool
{
    public function __construct(private BookingPageWrites $writes, private Identities $identities)
    {
    }

    /**
     * @param string $firstDay the first day nobody books the member, as 2026-12-21
     * @param string $lastDay  the last one, as 2027-01-04
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-inactive-period', description: 'Add to the booking page of the member a period nobody books them in, their holidays for instance. Answers the booking page, each period with the link removing it.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $firstDay, string $lastDay): array
    {
        return $this->writes->write(
            new InactivePeriodInput($firstDay, $lastDay),
            fn (InactivePeriodInput $input, string $memberId, \DateTimeImmutable $now): AddInactivePeriod => new AddInactivePeriod(
                memberId: $memberId,
                periodId: $this->identities->next(),
                firstDay: $input->firstDay ?? throw new \LogicException('A period validated has its first day'),
                lastDay: $input->lastDay ?? throw new \LogicException('A period validated has its last day'),
                executedAt: $now,
            ),
        );
    }
}
