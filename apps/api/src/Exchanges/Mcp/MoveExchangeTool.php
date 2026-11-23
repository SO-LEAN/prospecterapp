<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\MomentInput;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\MoveExchange\MoveExchange;

/**
 * `PATCH /api/exchanges/{id}/moment`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_move')]
final readonly class MoveExchangeTool
{
    public function __construct(private ExchangeWrites $writes, private ToolInputs $inputs)
    {
    }

    /**
     * @param string   $id                the exchange's id
     * @param string   $occurredAt        when it is now to happen (ISO 8601), in the future
     * @param int|null $durationInMinutes how long it now lasts, from 5 minutes to 8 hours. It keeps its duration when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'reschedule-exchange', description: 'Move a planned exchange to another moment in the future. It keeps its duration unless a new one is given. Only its holder can move it, and the holder\'s Google calendar follows. While the moment it was moved from is still in the future, the answer links a move back. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $occurredAt, ?int $durationInMinutes = null): array
    {
        $before = $this->writes->dispatched(
            $id,
            $this->inputs->mapped(MomentInput::class, ['occurredAt' => $occurredAt, 'durationInMinutes' => $durationInMinutes]),
            static fn (MomentInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): MoveExchange => new MoveExchange(prospectorId: $memberId, id: $exchange->id, plannedAt: $input->occurredAt ?? throw new \LogicException('The moment is validated'), executedAt: $now, durationInMinutes: $input->durationInMinutes),
        );

        return $this->writes->answer($before->id, movedFrom: $before->occurredAt);
    }
}
