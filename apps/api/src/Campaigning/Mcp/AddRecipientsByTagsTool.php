<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\RecipientSearchInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Recipient\AddRecipientsByTags\AddRecipientsByTags;

/**
 * `POST /api/campaigns/{id}/recipients/by-tags`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_add_recipients_by_tags')]
final readonly class AddRecipientsByTagsTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string       $id   the id of a frozen campaign
     * @param list<string> $tags the chosen tags; the campaign's channel tag counts with them within the search's 5 tags
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-recipients-by-tags', description: 'Add to a frozen campaign\'s recipients the contacts that carry every chosen tag and the channel\'s tag. A contact already kept is kept once. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, array $tags): array
    {
        return $this->writes->write(
            $id,
            new RecipientSearchInput($tags),
            static fn (RecipientSearchInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): AddRecipientsByTags => new AddRecipientsByTags(memberId: $memberId, campaignId: $campaign->id, tags: $input->tags ?? [], executedAt: $now),
        );
    }
}
