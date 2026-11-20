<?php

namespace App\Connectors\Mcp;

use App\Connectors\GoogleClientReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/organization/clients/google`, for an AI (ADR 0163).
 */
#[Adapts('api_me_google_client')]
final readonly class ConsultMyGoogleClientTool
{
    public function __construct(private ToolAccess $access, private GoogleClientReader $googleClientReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-client-google', description: 'The Google client of the account, as its administrator configured it, without its secret, and the notifications of Gmail; for an administrator.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_ADMIN');

        return $this->results->of($this->googleClientReader->read($this->access->member()));
    }
}
