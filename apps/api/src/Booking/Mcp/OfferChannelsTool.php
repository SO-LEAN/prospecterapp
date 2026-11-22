<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\Input\ChannelsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Booking\Application\OfferChannels\OfferChannels;

/**
 * `PATCH /api/me/booking-page/channels`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page_offer_channels')]
final readonly class OfferChannelsTool
{
    public function __construct(private BookingPageWrites $writes)
    {
    }

    /**
     * @param list<string> $channels how the member may be booked: call, videoCall, or both
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-booking-channels', description: 'Set how the member may be booked on their booking page, by call, by video call or both, given whole. Answers the booking page.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(array $channels): array
    {
        return $this->writes->write(
            new ChannelsInput($channels),
            static fn (ChannelsInput $input, string $memberId, \DateTimeImmutable $now): OfferChannels => new OfferChannels(
                memberId: $memberId,
                channels: $input->channels ?? throw new \LogicException('Channels validated are given'),
                executedAt: $now,
            ),
        );
    }
}
