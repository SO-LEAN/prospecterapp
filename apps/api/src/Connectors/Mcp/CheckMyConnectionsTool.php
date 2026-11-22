<?php

declare(strict_types=1);

namespace App\Connectors\Mcp;

use App\Connectors\MyConnectionsReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Connectors\Application\CheckMyConnections\CheckMyConnections;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/me/connections/checks`, for an AI (ADR 0163).
 */
#[Adapts('api_me_connections_check')]
final readonly class CheckMyConnectionsTool
{
    public function __construct(
        private ToolAccess $access,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyConnectionsReader $myConnectionsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'check-my-connections', description: 'Ask the providers (Google) whether the connections of the member still work: one whose access its provider refuses is to reconnect, on the screen. Answers their connections.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new CheckMyConnections($memberId, $this->clock->now()));

        return $this->results->of($this->myConnectionsReader->read($memberId));
    }
}
