<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\RecipientSearchInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\AddRecipientsByTags\AddRecipientsByTags;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

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
     * @param string       $id   the id of the campaign, frozen
     * @param list<string> $tags the tags chosen, the tag of the channel of the campaign counting with them in the 5 tags of the search
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-recipients-by-tags', description: 'Add to the recipients of a frozen campaign the contacts carrying every tag chosen and that of its channel, those already kept kept once; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, array $tags): array
    {
        return $this->writes->write(
            $id,
            new RecipientSearchInput($tags),
            static fn (RecipientSearchInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): AddRecipientsByTags => new AddRecipientsByTags(memberId: $memberId, campaignId: $campaign->id, tags: $input->tags ?? [], executedAt: $now),
        );
    }
}
