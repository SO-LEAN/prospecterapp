<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\DismissalInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\DismissRecipient\DismissRecipient;

/**
 * `POST /api/campaigns/{id}/recipients/{contactId}/dismissal`, for an AI (ADR 0163), which asks its member before
 * calling it: a recipient dismissed is never handled again in the campaign.
 */
#[Adapts('api_campaigns_dismiss_recipient')]
final readonly class DismissRecipientTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string  $id        the id of the campaign, started
     * @param string  $contactId the id of the contact, one of its recipients still to handle
     * @param ?string $reason    why, when said
     */
    #[McpTool(name: 'dismiss-campaign-recipient', description: 'Dismiss a recipient of a started campaign without an exchange, for good: the campaign ends once nobody is left to handle; needs the right to manage the personal data. Answers nothing.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $contactId, ?string $reason = null): void
    {
        $this->writes->dispatched(
            $id,
            new DismissalInput($reason),
            static fn (DismissalInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): DismissRecipient => new DismissRecipient($memberId, $campaign->id, $contactId, $input->reason, $now),
        );
    }
}
