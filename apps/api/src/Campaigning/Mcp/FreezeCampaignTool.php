<?php

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\FreezeCampaign\FreezeCampaign;

/**
 * `POST /api/campaigns/{id}/freezing`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_freeze')]
final readonly class FreezeCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of the campaign, a draft
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'freeze-campaign', description: 'Keep the contacts a draft campaign targets now as its recipients, for good: its channel and its tags no longer change; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): FreezeCampaign => new FreezeCampaign($memberId, $campaign->id, $now),
        );
    }
}
