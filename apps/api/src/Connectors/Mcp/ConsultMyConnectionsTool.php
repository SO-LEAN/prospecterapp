<?php

namespace App\Connectors\Mcp;

use App\Connectors\MyConnectionsReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/connections`, for an AI (ADR 0163).
 */
final readonly class ConsultMyConnectionsTool
{
    public function __construct(private ToolAccess $access, private MyConnectionsReader $myConnectionsReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-mes-connexions', description: 'Les connexions du membre aux services extérieurs (Google Agenda, l\'import des e-mails de Gmail), dans l\'état vu en dernier.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myConnectionsReader->read($this->access->member()->memberId()));
    }
}
