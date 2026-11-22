<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\ReportInput;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MarkExchangeDone\MarkExchangeDone;

/**
 * `POST /api/exchanges/{id}/done`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_mark_done')]
final readonly class MarkExchangeDoneTool
{
    public function __construct(private ExchangeWrites $writes, private ToolInputs $inputs)
    {
    }

    /**
     * @param string      $id         the id of the exchange
     * @param string|null $text       its report, which replaces its agenda
     * @param string|null $occurredAt when it really took place (ISO 8601), its planned moment when left out; never in the future
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'mark-exchange-done', description: 'Mark done a planned exchange whose time has come, with its report and the moment it really took place; by its holder. It is no longer to process. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, ?string $text = null, ?string $occurredAt = null): array
    {
        return $this->writes->write(
            $id,
            $this->inputs->mapped(ReportInput::class, ['text' => $text, 'occurredAt' => $occurredAt]),
            static fn (ReportInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): MarkExchangeDone => new MarkExchangeDone(prospectorId: $memberId, id: $exchange->id, occurredAt: $input->occurredAt ?? $exchange->occurredAt, report: $input->text, executedAt: $now),
        );
    }
}
