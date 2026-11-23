<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\FreezeCampaign\FreezeCampaign;

/**
 * `POST /api/campaigns/{id}/freeze`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_freeze')]
final readonly class FreezeCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of a draft campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'freeze-campaign', description: 'Freeze a draft campaign: the contacts it targets now become its recipients for good, and its channel and tags no longer change. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): FreezeCampaign => new FreezeCampaign($memberId, $campaign->id, $now),
        );
    }
}
