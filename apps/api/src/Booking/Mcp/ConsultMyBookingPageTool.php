<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\MyBookingPageReader;
use App\Security\ToolAccess;
use App\Shared\Input\MyClockQuery;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/booking-page`, for an AI (ADR 0163).
 */
#[Adapts('api_booking_page')]
final readonly class ConsultMyBookingPageTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private MyBookingPageReader $myBookingPageReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param ?string $timeZone the time zone of the member, by its name (Europe/Paris); UTC otherwise
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-my-booking-page', description: 'The booking page of the member: open or not, its public link, its slots by day, its inactive periods, its channels.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $clock = $this->inputs->valid(new MyClockQuery($timeZone));

        return $this->results->of($this->myBookingPageReader->read($this->access->member()->memberId(), $clock->timeZone));
    }
}
