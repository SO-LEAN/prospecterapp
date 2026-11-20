<?php

namespace App\Contacts\Mcp;

use App\Contacts\AgendaReader;
use App\Contacts\Input\CalendarQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `GET /api/me/calendar`, for an AI (ADR 0163).
 */
#[Adapts('api_me_calendar')]
final readonly class ShowCalendarTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private AgendaReader $agendaReader,
        private ClockInterface $clock,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $from     the start of the first day of the period, with the offset of the member (2026-10-12T00:00:00+02:00)
     * @param string  $to       the start of the day after its last, excluded; six weeks after `from` at most
     * @param ?string $timeZone the time zone of the member, by its name (Europe/Paris); the offset of `from` otherwise
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-agenda', description: 'The calendar of the member: the exchanges they hold in a period of six weeks at most, whatever their status, and those late.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $from, string $to, ?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $period = $this->inputs->mapped(CalendarQuery::class, ['from' => $from, 'to' => $to, 'timeZone' => $timeZone]);

        return $this->results->of($this->agendaReader->readCalendar($this->access->member()->memberId(), $period->from, $period->to, $period->read($this->clock->now())));
    }
}
