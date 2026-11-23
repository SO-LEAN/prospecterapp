<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignMessages;
use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns/{id}/messages/{contactId}`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_message')]
final readonly class RenderCampaignMessageTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private CampaignMessages $messages,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id        the campaign's id
     * @param string $contactId the id of the contact that the message is rendered for
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-campaign-message', description: 'Get a campaign\'s message rendered for one of its contacts. Needs the right to manage personal data. Returns the message\'s subject and text.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, string $contactId): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->messages->renderedFor($member->memberId(), $campaign->id, $contactId));
    }
}
