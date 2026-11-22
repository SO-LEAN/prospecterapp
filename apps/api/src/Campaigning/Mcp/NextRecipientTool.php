<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Campaigning\NextRecipientReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Input\MyClockQuery;
use App\Shared\Mcp\ToolInputs;
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
        private ToolInputs $inputs,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id       the id of the campaign
     * @param ?string $timeZone the member's time zone, by its name (Europe/Paris). Its daily cap's day follows it. UTC otherwise
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get-next-recipient',
        description: 'The next recipient of a started campaign you lead for your member, how to reach them, the message rendered for them and what to do with it (its instruction: send it as is, or adapt it to the person), and how many are left to handle. Send it yourself, then note it by log-sent-message, or dismiss the recipient by dismiss-campaign-recipient when they cannot be reached; ask again for the next one. Once its daily cap is reached, it warns you: stop, and ask your member whether to go beyond it. Refused once the campaign is no longer led (campaignNotRunning): stopped, or ended once nobody is left to handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(string $id, ?string $timeZone = null): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        $clock = $this->inputs->valid(new MyClockQuery($timeZone));

        return $this->results->of($this->nextRecipients->read($member->memberId(), $campaign->id, $clock->timeZone));
    }
}
