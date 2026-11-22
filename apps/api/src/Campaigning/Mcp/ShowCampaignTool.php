<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
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
        private CampaignReader $campaignReader,
        private ToolInputs $inputs,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id       the id of the campaign
     * @param ?string $timeZone the member's time zone, by its name (Europe/Paris). The recipients processed today follow it. UTC otherwise
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-campaign', description: 'A campaign: its name, its channel, its targeted tags, its message, its status, its progress, its daily cap and its recipients processed today, and the link to its recipients, with the right to manage the personal data.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, ?string $timeZone = null): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        $clock = $this->inputs->valid(new MyClockQuery($timeZone));

        return $this->results->of($this->campaignReader->read($member->memberId(), $campaign->id, $clock->timeZone));
    }
}
