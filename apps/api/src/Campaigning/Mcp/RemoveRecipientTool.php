<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\RemoveRecipient\RemoveRecipient;

/**
 * `DELETE /api/campaigns/{id}/recipients/{contactId}`, for an AI (ADR 0163), which asks its member before calling it.
 */
#[Adapts('api_campaigns_remove_recipient')]
final readonly class RemoveRecipientTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id        the id of the campaign, frozen
     * @param string $contactId the id of the contact, one of its recipients
     */
    #[McpTool(name: 'remove-campaign-recipient', description: 'Take a recipient off a frozen campaign, never the last one; needs the right to manage the personal data. Answers nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $contactId): void
    {
        $this->writes->changed(
            $id,
            static fn (CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): RemoveRecipient => new RemoveRecipient(memberId: $memberId, campaignId: $campaign->id, contactId: $contactId, executedAt: $now),
        );
    }
}
