<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\StartCampaign\StartCampaign;

/**
 * `POST /api/campaigns/{id}/start`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_start')]
final readonly class StartCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of a frozen campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'start-campaign', description: 'Start a frozen campaign. Its recipients, message and channel no longer change. From then on it is stopped, never discarded. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->change(
            $id,
            static fn (CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): StartCampaign => new StartCampaign($memberId, $campaign->id, $now),
        );
    }
}
