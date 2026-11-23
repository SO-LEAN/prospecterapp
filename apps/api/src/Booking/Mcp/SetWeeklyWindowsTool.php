<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\Input\WindowsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\BookingPage\SetWeeklyWindows\SetWeeklyWindows;

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
     * @param string                                                 $timeZone the member's time zone, by its name (Europe/Paris). The windows are read on its clock
     * @param list<array{weekday: string, from: string, to: string}> $windows  every window, each with a weekday (monday…sunday) and its hours (14:00 to 17:00). A window left out is removed
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-booking-slots', description: 'Set the weekly windows in which bookers can book the member through their booking page. The list replaces the current one. Returns the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $timeZone, array $windows): array
    {
        return $this->writes->write(
            new WindowsInput($timeZone, $windows),
            static fn (WindowsInput $input, string $memberId, \DateTimeImmutable $now): SetWeeklyWindows => new SetWeeklyWindows(
                memberId: $memberId,
                windows: $input->windows ?? throw new \LogicException('Validated windows are given'),
                timeZone: $input->timeZone ?? throw new \LogicException('Validated windows have their time zone'),
                executedAt: $now,
            ),
            $timeZone,
        );
    }
}
