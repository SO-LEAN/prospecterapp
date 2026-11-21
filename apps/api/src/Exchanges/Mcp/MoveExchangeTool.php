<?php

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
     * @param string   $id                the id of the exchange
     * @param string   $occurredAt        when it is to happen now (ISO 8601), in the future
     * @param int|null $durationInMinutes how long it lasts now, from 5 minutes to 8 hours; its own when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'reschedule-exchange', description: 'Move a planned exchange to another moment to come, for a new duration or its own; by its holder, its calendar at Google following. Answers the exchange, linking moving it back while the moment it was moved from is to come.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $occurredAt, ?int $durationInMinutes = null): array
    {
        $before = $this->writes->dispatched(
            $id,
            $this->inputs->mapped(MomentInput::class, ['occurredAt' => $occurredAt, 'durationInMinutes' => $durationInMinutes]),
            static fn (MomentInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): MoveExchange => new MoveExchange($memberId, $exchange->id, $input->occurredAt ?? throw new \LogicException('The moment is validated'), $now, $input->durationInMinutes),
        );

        return $this->writes->answer($before->id, movedFrom: $before->occurredAt);
    }
}
