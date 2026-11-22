<?php

declare(strict_types=1);

namespace App\Dashboard\Mcp;

use App\Dashboard\DashboardReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/dashboard`, for an AI (ADR 0163).
 */
#[Adapts('api_dashboard')]
final readonly class ShowDashboardTool
{
    public function __construct(private ToolAccess $access, private DashboardReader $dashboardReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-dashboard', description: 'The dashboard of the account: its figures (organizations, contacts, exchanges) and the organizations to place on a map.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->dashboardReader->read($this->access->member()->memberId()));
    }
}
