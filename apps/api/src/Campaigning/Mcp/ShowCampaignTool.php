<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
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
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-campagne', description: 'Une campagne : son nom, son canal, ses tags ciblés, son message, son statut, sa progression, et le lien vers ses destinataires ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->campaignReader->read($member->memberId(), $campaign->id));
    }
}
