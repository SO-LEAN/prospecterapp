<?php

namespace App\Dashboard\Mcp;

use App\Dashboard\DashboardReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/dashboard`, for an AI (ADR 0163).
 */
final readonly class ShowDashboardTool
{
    public function __construct(private ToolAccess $access, private DashboardReader $dashboardReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-tableau-de-bord', description: 'Le tableau de bord du compte : ses chiffres (organisations, contacts, échanges) et les organisations à placer sur une carte.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->dashboardReader->read($this->access->member()->memberId()));
    }
}
