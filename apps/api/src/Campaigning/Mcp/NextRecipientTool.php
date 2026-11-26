<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Campaigning\NextRecipientComposition;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Input\MyClockQuery;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * No endpoint: no screen asks for it, because a campaign's screen lists its recipients (ADR 0163). Its access rule is
 * the one of a campaign's message, `get-campaign-message`.
 */
final readonly class NextRecipientTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private NextRecipientComposition $nextRecipients,
        private ToolInputs $inputs,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id       the campaign's id
     * @param ?string $timeZone the member's time zone, by name (Europe/Paris). The daily cap's day follows it. UTC when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get-next-recipient',
        description: 'Get the next recipient of a started campaign that you run for your member. Send the message yourself, then log it with log-sent-message. When the recipient cannot be reached, dismiss them with dismiss-campaign-recipient. Then ask for the next one. Once the daily cap is reached, the answer warns you: stop, and ask your member whether to go beyond it. Refused once the campaign no longer runs (campaignNotRunning): it was stopped, or it ended once nobody was left to handle. Returns how to reach the recipient, the message rendered for them, its instruction (send it as is, or adapt it to the person), and how many recipients are left to handle.',
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
