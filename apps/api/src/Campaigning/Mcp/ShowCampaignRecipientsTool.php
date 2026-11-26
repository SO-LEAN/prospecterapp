<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Campaigning\RecipientsComposition;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns/{id}/recipients`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_recipients')]
final readonly class ShowCampaignRecipientsTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private RecipientsComposition $recipients,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the campaign's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-campaign-recipients', description: 'List a frozen or started campaign\'s recipients. Needs the right to manage personal data. Returns each recipient with their status (pending, processed, dismissed, erased).', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->recipients->read($member->memberId(), $campaign->id));
    }
}
