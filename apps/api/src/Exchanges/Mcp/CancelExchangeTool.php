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
 * `POST /api/exchanges/{id}/cancellation`, for an AI (ADR 0163), which asks its member before calling it: a
 * cancellation is never taken back.
 */
#[Adapts('api_exchanges_cancel')]
final readonly class CancelExchangeTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string      $id        the id of the exchange
     * @param string|null $canceller who called it off, when said: prospector or contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'cancel-exchange', description: 'Cancel a planned exchange, saying who called it off if known; by its holder. It stays in the timelines, cancelled, and leaves the calendar at Google; it is never planned again. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, ?string $canceller = null): array
    {
        return $this->writes->write(
            $id,
            new CancellationInput($canceller),
            static fn (CancellationInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CancelExchange => new CancelExchange(prospectorId: $memberId, id: $exchange->id, canceller: $input->canceller, executedAt: $now),
        );
    }
}
