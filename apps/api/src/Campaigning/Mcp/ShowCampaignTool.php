<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignComposition;
use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Input\MyClockQuery;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_show')]
final readonly class ShowCampaignTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private CampaignComposition $campaignComposition,
        private ToolInputs $inputs,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id       the campaign's id
     * @param ?string $timeZone the member's time zone, by name (Europe/Paris). The recipients processed today follow it. UTC when left out
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-campaign', description: 'Get a campaign. Needs the right to manage personal data. Returns its name, channel, targeted tags, message, status, progress, daily cap, recipients processed today, and the link to its recipients.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, ?string $timeZone = null): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        $clock = $this->inputs->valid(new MyClockQuery($timeZone));

        return $this->results->of($this->campaignComposition->read($member->memberId(), $campaign->id, $clock->timeZone));
    }
}
