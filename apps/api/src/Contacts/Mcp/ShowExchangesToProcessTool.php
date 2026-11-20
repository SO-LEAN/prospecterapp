<?php

namespace App\Contacts\Mcp;

use App\Contacts\AgendaReader;
use App\Contacts\Input\ReaderClockQuery;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `GET /api/me/exchanges-to-process`, for an AI (ADR 0163).
 */
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
     * @param ?string $timeZone the time zone of the member, by its name (Europe/Paris); UTC otherwise
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-echanges-a-traiter', description: 'Ce que le membre a à traiter : ses échanges planifiés, terminés et laissés sans suite, les plus anciens d\'abord.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $clock = $this->inputs->valid(new ReaderClockQuery($timeZone));

        return $this->results->of($this->agendaReader->readExchangesToProcess($this->access->member()->memberId(), $clock->read($this->clock->now())));
    }
}
