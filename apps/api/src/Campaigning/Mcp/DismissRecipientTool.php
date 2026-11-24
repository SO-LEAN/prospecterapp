<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\DismissalInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Recipient\DismissRecipient\DismissRecipient;

/**
 * `POST /api/campaigns/{id}/recipients/{contactId}/dismissal`, for an AI (ADR 0163). The AI asks its member before it
 * calls this tool, because a dismissed recipient is never handled again in the campaign.
 */
#[Adapts('api_campaigns_dismiss_recipient')]
final readonly class DismissRecipientTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string  $id        the id of a started campaign
     * @param string  $contactId the contact's id, one of its recipients still to handle
     * @param ?string $reason    the reason, when given
     */
    #[McpTool(name: 'dismiss-campaign-recipient', description: 'Dismiss a started campaign\'s recipient without an exchange, for good. The campaign ends once nobody is left to handle. Needs the right to manage personal data. Returns nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $contactId, ?string $reason = null): void
    {
        $this->writes->dispatched(
            $id,
            new DismissalInput($reason),
            static fn (DismissalInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): DismissRecipient => new DismissRecipient(memberId: $memberId, campaignId: $campaign->id, contactId: $contactId, reason: $input->reason, executedAt: $now),
        );
    }
}
