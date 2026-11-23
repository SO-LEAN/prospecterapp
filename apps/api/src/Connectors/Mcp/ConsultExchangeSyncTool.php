<?php

declare(strict_types=1);

namespace App\Connectors\Mcp;

use App\Connectors\ExchangeSyncReader;
use App\Exchanges\ExchangeResolver;
use App\Exchanges\ExchangeVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/exchanges/{id}/google-sync`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_google_sync')]
final readonly class ConsultExchangeSyncTool
{
    public function __construct(
        private ToolAccess $access,
        private ExchangeResolver $exchanges,
        private ExchangeSyncReader $exchangeSyncReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the exchange's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-exchange-google-sync', description: 'Get the state of an exchange\'s push into its holder\'s Google Calendar. Returns the push state (pending, synced or failed) and why.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $exchange = $this->exchanges->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ExchangeVoter::ACCESS, $exchange);

        return $this->results->of($this->exchangeSyncReader->read($member->memberId(), $exchange->id));
    }
}
