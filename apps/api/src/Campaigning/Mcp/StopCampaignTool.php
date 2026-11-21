<?php

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\StopCampaign\StopCampaign;

/**
 * `POST /api/campaigns/{id}/stopping`, for an AI (ADR 0163), which asks its member before calling it: a campaign
 * stopped is never started again.
 */
#[Adapts('api_campaigns_stop')]
final readonly class StopCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of the campaign, started
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'stop-campaign', description: 'Stop a started campaign for good, kept for the team to read; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): StopCampaign => new StopCampaign($memberId, $campaign->id, $now),
        );
    }
}
