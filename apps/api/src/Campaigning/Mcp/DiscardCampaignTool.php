<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\Campaign\DiscardCampaign\DiscardCampaign;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/** `DELETE /api/campaigns/{id}`, for an AI (ADR 0163). The AI asks its member before it calls this tool. */
#[Adapts('api_campaigns_discard')]
final readonly class DiscardCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of a campaign that never started
     */
    #[McpTool(name: 'delete-campaign', description: 'Discard a campaign that never started. Needs the right to manage personal data. Returns nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->changed(
            $id,
            static fn (CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): DiscardCampaign => new DiscardCampaign($memberId, $campaign->id, $now),
        );
    }
}
