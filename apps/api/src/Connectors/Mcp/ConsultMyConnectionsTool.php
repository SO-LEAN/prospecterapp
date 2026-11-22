<?php

declare(strict_types=1);

namespace App\Connectors\Mcp;

use App\Connectors\MyConnectionsReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/connections`, for an AI (ADR 0163).
 */
#[Adapts('api_me_connections')]
final readonly class ConsultMyConnectionsTool
{
    public function __construct(private ToolAccess $access, private MyConnectionsReader $myConnectionsReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-my-connections', description: 'The connections of the member to the services outside (Google Calendar, the import of the e-mails of Gmail), in the state last seen.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myConnectionsReader->read($this->access->member()->memberId()));
    }
}
