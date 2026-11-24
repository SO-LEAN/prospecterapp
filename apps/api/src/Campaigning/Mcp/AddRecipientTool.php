<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\RecipientInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Recipient\AddRecipient\AddRecipient;

/**
 * `POST /api/campaigns/{id}/recipients`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_add_recipient')]
final readonly class AddRecipientTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string $id        the id of a frozen campaign
     * @param string $contactId the id of one of the account's contacts
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-campaign-recipient', description: 'Add a chosen contact to a frozen campaign\'s recipients. A contact is kept once. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $contactId): array
    {
        return $this->writes->write(
            $id,
            new RecipientInput($contactId),
            static fn (RecipientInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): AddRecipient => new AddRecipient(memberId: $memberId, campaignId: $campaign->id, contactId: (string) $input->contactId, executedAt: $now),
        );
    }
}
