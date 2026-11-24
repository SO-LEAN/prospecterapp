<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Exchange\MarkExchangeMissed\MarkExchangeMissed;

/**
 * `POST /api/exchanges/{id}/missed`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_mark_missed')]
final readonly class MarkExchangeMissedTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string $id the exchange's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'mark-exchange-missed', description: 'Mark a planned exchange missed once its time has come: the contact did not come or did not answer. Only its holder can mark it. The exchange then leaves the list to process. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): MarkExchangeMissed => new MarkExchangeMissed($memberId, $exchange->id, $now),
        );
    }
}
