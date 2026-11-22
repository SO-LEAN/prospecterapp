<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MarkExchangeMissed\MarkExchangeMissed;

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
     * @param string $id the id of the exchange
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'mark-exchange-missed', description: 'Mark missed a planned exchange whose time has come: the contact did not come or answer; by its holder. It is no longer to process. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): MarkExchangeMissed => new MarkExchangeMissed($memberId, $exchange->id, $now),
        );
    }
}
