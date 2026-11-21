<?php

namespace App\Booking\Mcp;

use App\Booking\Input\WindowsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\SetWeeklyWindows\SetWeeklyWindows;

/**
 * `PATCH /api/me/booking-page/windows`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page_set_windows')]
final readonly class SetWeeklyWindowsTool
{
    public function __construct(private BookingPageWrites $writes)
    {
    }

    /**
     * @param string                                                 $timeZone the time zone of the member, by its name (Europe/Paris), the windows read on its clock
     * @param list<array{weekday: string, from: string, to: string}> $windows  each a weekday (monday…sunday) and its hours (14:00 to 17:00), all of them: those left out are removed
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-booking-slots', description: 'Set the weekly windows the member may be booked in on their booking page, given whole. Answers the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $timeZone, array $windows): array
    {
        return $this->writes->write(
            new WindowsInput($timeZone, $windows),
            static fn (WindowsInput $input, string $memberId, \DateTimeImmutable $now): SetWeeklyWindows => new SetWeeklyWindows(
                memberId: $memberId,
                windows: $input->windows ?? throw new \LogicException('Windows validated are given'),
                timeZone: $input->timeZone ?? throw new \LogicException('Windows validated have their time zone'),
                executedAt: $now,
            ),
            $timeZone,
        );
    }
}
