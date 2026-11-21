<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Campaigning\NextRecipientReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * No endpoint: no screen asks it, the screen of a campaign listing its recipients (ADR 0163). Its access is that of
 * the message of a campaign, `get-campaign-message`.
 */
final readonly class NextRecipientTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private NextRecipientReader $nextRecipients,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get-next-recipient',
        description: 'The next recipient of a started campaign you lead for your member, how to reach them, the message rendered for them and what to do with it (its instruction: send it as is, or adapt it to the person), and how many are left to handle. Send it yourself, then note it by log-sent-message, or dismiss the recipient by dismiss-campaign-recipient when they cannot be reached; ask again for the next one. Refused once the campaign is no longer led (campaignNotRunning): stopped, or ended once nobody is left to handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->nextRecipients->read($member->memberId(), $campaign->id));
    }
}
