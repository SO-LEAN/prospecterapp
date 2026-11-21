<?php

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\StartCampaign\StartCampaign;

/**
 * `POST /api/campaigns/{id}/starting`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_start')]
final readonly class StartCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id the id of the campaign, frozen
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'demarrer-campagne', description: 'Start a frozen campaign: its recipients, its message and its channel no longer change, and it is stopped, never discarded; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): StartCampaign => new StartCampaign($memberId, $campaign->id, $now),
        );
    }
}
