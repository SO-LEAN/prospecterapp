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
     * @param string $firstDay the first day when nobody can book the member, such as 2026-12-21
     * @param string $lastDay  the last such day, such as 2027-01-04
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-inactive-period', description: 'Add an inactive period to the member\'s booking page: days when nobody can book them, such as their holidays. Returns the booking page. Each period has the link that removes it.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $firstDay, string $lastDay): array
    {
        return $this->writes->write(
            new InactivePeriodInput($firstDay, $lastDay),
            fn (InactivePeriodInput $input, string $memberId, \DateTimeImmutable $now): AddInactivePeriod => new AddInactivePeriod(
                memberId: $memberId,
                periodId: $this->identities->next(),
                firstDay: $input->firstDay ?? throw new \LogicException('A validated period has its first day'),
                lastDay: $input->lastDay ?? throw new \LogicException('A validated period has its last day'),
                executedAt: $now,
            ),
        );
    }
}
