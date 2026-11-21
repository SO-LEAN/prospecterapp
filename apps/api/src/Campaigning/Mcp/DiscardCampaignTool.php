<?php

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\DiscardCampaign\DiscardCampaign;

/**
 * `DELETE /api/campaigns/{id}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_campaigns_discard')]
final readonly class DiscardCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of the campaign, never started
     */
    #[McpTool(name: 'delete-campaign', description: 'Discard a campaign never started; needs the right to manage the personal data. Answers nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): void
    {
        $this->writes->dispatched(
            $id,
            null,
            static fn (null $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): DiscardCampaign => new DiscardCampaign($memberId, $campaign->id, $now),
        );
    }
}
