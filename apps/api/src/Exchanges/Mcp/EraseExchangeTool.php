<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;

/**
 * `DELETE /api/exchanges/{id}`, for an AI (ADR 0163). The AI asks its member before it calls this tool.
 */
#[Adapts('api_exchanges_erase')]
final readonly class EraseExchangeTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string $id the exchange's id
     */
    #[McpTool(name: 'delete-exchange', description: 'Erase an exchange from every timeline. Needs the right to manage personal data. Returns nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->changed(
            $id,
            static fn (ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): EraseExchange => new EraseExchange($memberId, $exchange->id, $now),
            User::PERSONAL_DATA,
        );
    }
}
