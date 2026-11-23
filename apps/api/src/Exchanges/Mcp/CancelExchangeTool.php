<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\CancellationInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\CancelExchange\CancelExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;

/**
 * `POST /api/exchanges/{id}/cancellation`, for an AI (ADR 0163). The AI asks its member before it calls this tool,
 * because a cancellation is never taken back.
 */
#[Adapts('api_exchanges_cancel')]
final readonly class CancelExchangeTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string      $id        the exchange's id
     * @param string|null $canceller who called it off, when known: prospector or contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'cancel-exchange', description: 'Cancel a planned exchange, and say who called it off if known. Only its holder can cancel it. The exchange stays in the timelines as cancelled, and leaves the Google calendar. It is never planned again. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, ?string $canceller = null): array
    {
        return $this->writes->write(
            $id,
            new CancellationInput($canceller),
            static fn (CancellationInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CancelExchange => new CancelExchange(prospectorId: $memberId, id: $exchange->id, canceller: $input->canceller, executedAt: $now),
        );
    }
}
