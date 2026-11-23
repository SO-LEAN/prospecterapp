<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\AgendaReader;
use App\Contacts\Input\ReaderClockQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `GET /api/me/exchanges-to-process`, for an AI (ADR 0163).
 */
#[Adapts('api_me_exchanges_to_process')]
final readonly class ShowExchangesToProcessTool
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
     * @param ?string $timeZone the member's time zone, by its name (Europe/Paris). Without it, UTC applies
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-exchanges-to-handle', description: 'Lists what the member has to process: their planned exchanges that have ended and are still unhandled, the oldest first.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $clock = $this->inputs->valid(new ReaderClockQuery($timeZone));

        return $this->results->of($this->agendaReader->readExchangesToProcess($this->access->member()->memberId(), $clock->read($this->clock->now())));
    }
}
