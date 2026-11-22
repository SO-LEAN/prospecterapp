<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\DailyCapInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\SetDailyCap\SetDailyCap;

/**
 * `PATCH /api/campaigns/{id}/daily-cap`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_set_daily_cap')]
final readonly class SetDailyCapTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id       the id of the campaign, neither stopped nor ended
     * @param ?int   $dailyCap the most recipients processed a day, at least 1; none takes the cap off
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-campaign-daily-cap', description: 'Set the daily cap of a campaign, the most recipients processed a day, or take it off, until it is stopped or ended: a guard you stop at to ask your member whether to go beyond it; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?int $dailyCap = null): array
    {
        return $this->writes->write(
            $id,
            new DailyCapInput($dailyCap),
            static fn (DailyCapInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): SetDailyCap => new SetDailyCap(memberId: $memberId, campaignId: $campaign->id, dailyCap: $input->dailyCap, executedAt: $now),
        );
    }
}
