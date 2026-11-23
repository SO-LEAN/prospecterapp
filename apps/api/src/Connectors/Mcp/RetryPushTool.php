<?php

declare(strict_types=1);

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
 * `POST /api/exchanges/{id}/google-sync/retries`, for an AI (ADR 0163). It reaches its member's calendar at Google.
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
     * @param string $id the exchange's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'retry-google-sync', description: 'Push an exchange again, as it is now, into its holder\'s Google calendar after Google refused it. Only its holder can use this tool. Returns its push state.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(string $id): array
    {
        $exchange = $this->writes->changed(
            $id,
            static fn (ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): RetryPush => new RetryPush($memberId, $exchange->id, $now),
        );

        return $this->results->of($this->exchangeSyncReader->read($this->access->member()->memberId(), $exchange->id));
    }
}
