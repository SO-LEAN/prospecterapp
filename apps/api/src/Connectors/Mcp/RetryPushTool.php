<?php

namespace App\Connectors\Mcp;

use App\Connectors\ExchangeSyncReader;
use App\Exchanges\Mcp\ExchangeWrites;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\RetryPush\RetryPush;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;

/**
 * `POST /api/exchanges/{id}/google-sync/retries`, for an AI (ADR 0163): it reaches the calendar of its member at
 * Google.
 */
#[Adapts('api_exchanges_google_sync_retry')]
final readonly class RetryPushTool
{
    public function __construct(
        private ExchangeWrites $writes,
        private ToolAccess $access,
        private ExchangeSyncReader $exchangeSyncReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the exchange
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'relancer-synchronisation-google', description: 'Push an exchange into the calendar of its holder at Google again, as it is now, once Google refused it; by its holder. Answers where its push stands.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): array
    {
        $exchange = $this->writes->dispatched(
            $id,
            null,
            static fn (null $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): RetryPush => new RetryPush($memberId, $exchange->id, $now),
        );

        return $this->results->of($this->exchangeSyncReader->read($this->access->member()->memberId(), $exchange->id));
    }
}
