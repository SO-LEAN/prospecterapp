<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\AgendaComposition;
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
        private AgendaComposition $agenda,
        private ClockInterface $clock,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $from     the start of the period's first day, with the member's offset (2026-10-12T00:00:00+02:00)
     * @param string  $to       the start of the day after the period's last day, excluded. It is six weeks after `from` at most
     * @param ?string $timeZone the member's time zone, by its name (Europe/Paris). Without it, the offset of `from` applies
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-calendar', description: 'Get the member\'s calendar: the exchanges they hold in a period of six weeks at most, whatever their status. It also says which ones are late.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $from, string $to, ?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $period = $this->inputs->mapped(CalendarQuery::class, ['from' => $from, 'to' => $to, 'timeZone' => $timeZone]);

        return $this->results->of($this->agenda->readCalendar($this->access->member()->memberId(), $period->start(), $period->end(), $period->read($this->clock->now())));
    }
}
