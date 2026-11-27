<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\AgendaComposition;
use App\Contacts\Input\PeriodQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `GET /api/me/day`, for an AI (ADR 0163).
 */
#[Adapts('api_me_day')]
final readonly class ShowMyDayTool
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
     * @param string $from the start of the day, with the member's offset (2026-10-15T00:00:00+02:00)
     * @param string $to   the start of the day after, excluded
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-my-day', description: 'Get the member\'s day: what they still have planned that day, then the next five exchanges.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $from, string $to): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $day = $this->inputs->mapped(PeriodQuery::class, ['from' => $from, 'to' => $to]);

        return $this->results->of($this->agenda->readMyDay($this->access->member()->memberId(), $day->start(), $day->end(), $this->clock->now()));
    }
}
