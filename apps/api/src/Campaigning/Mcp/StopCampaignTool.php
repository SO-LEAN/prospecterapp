<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\Campaign\StopCampaign\StopCampaign;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/**
 * `POST /api/campaigns/{id}/stop`, for an AI (ADR 0163). The AI asks its member before it calls this tool, because a
 * stopped campaign never starts again.
 */
#[Adapts('api_campaigns_stop')]
final readonly class StopCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of a started campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'stop-campaign', description: 'Stop a started campaign for good. It stays for the team to read. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): StopCampaign => new StopCampaign($memberId, $campaign->id, $now),
        );
    }
}
