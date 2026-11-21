<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\RecipientInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\AddRecipient\AddRecipient;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

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
     * @param string $id        the id of the campaign, frozen
     * @param string $contactId the id of a contact of the account
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add-campaign-recipient', description: 'Add a contact chosen to the recipients of a frozen campaign, kept once; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $contactId): array
    {
        return $this->writes->write(
            $id,
            new RecipientInput($contactId),
            static fn (RecipientInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): AddRecipient => new AddRecipient($memberId, $campaign->id, (string) $input->contactId, $now),
        );
    }
}
