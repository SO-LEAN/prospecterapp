<?php

namespace App\Booking\Mcp;

use App\Booking\Input\MyClockQuery;
use App\Booking\MyBookingPageReader;
use App\Security\ToolAccess;
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
    #[McpTool(name: 'lire-ma-page-de-reservation', description: 'La page de réservation du membre : ouverte ou non, son lien public, ses créneaux par jour, ses périodes d\'inactivité, ses canaux.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $clock = $this->inputs->valid(new MyClockQuery($timeZone));

        return $this->results->of($this->myBookingPageReader->read($this->access->member()->memberId(), $clock->timeZone));
    }
}
