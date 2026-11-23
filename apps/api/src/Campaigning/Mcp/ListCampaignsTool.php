<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_list')]
final readonly class ListCampaignsTool
{
    public function __construct(private ToolAccess $access, private CampaignReader $campaignReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-campaigns', description: 'List the team\'s campaigns. Needs the right to manage personal data. Returns each campaign with its target count, its status and its progress.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->campaignReader->list($this->access->member()->memberId()));
    }
}
