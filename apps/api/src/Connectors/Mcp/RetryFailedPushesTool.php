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
use Prospecter\Core\Connectors\Application\Calendar\RetryFailedPushes\RetryFailedPushes;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/me/connections/google/retries`, for an AI (ADR 0163). It reaches its member's calendar at Google.
 */
#[Adapts('api_me_connections_google_retry_failed')]
final readonly class RetryFailedPushesTool
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
    #[McpTool(name: 'retry-failed-google-syncs', description: 'Push again into the member\'s Google calendar each of their exchanges that Google refused. Returns their connections, with how many exchanges are synced and how many failed.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new RetryFailedPushes($memberId, $this->clock->now()));

        return $this->results->of($this->myConnectionsReader->read($memberId));
    }
}
