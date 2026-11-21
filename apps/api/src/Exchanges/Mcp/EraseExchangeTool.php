<?php

namespace App\Exchanges\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;

/**
 * `DELETE /api/exchanges/{id}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_exchanges_erase')]
final readonly class EraseExchangeTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string $id the id of the exchange
     */
    #[McpTool(name: 'delete-exchange', description: 'Erase an exchange from every timeline; needs the right to manage the personal data. Answers nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->dispatched(
            $id,
            null,
            static fn (null $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): EraseExchange => new EraseExchange($memberId, $exchange->id, $now),
            User::PERSONAL_DATA,
        );
    }
}
