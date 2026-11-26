<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignLinksResource;
use App\Campaigning\Resource\CampaignListLinksResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Campaign\ListCampaigns\ListCampaignsOutput;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/**
 * Presents what Campaigns shows of the team's campaigns, newest first: each one linked as the campaign's presenter
 * links it. The list offers to prepare a campaign. The presenter is an object of the call (ADR 0085).
 */
final class CampaignListPresenter implements ListCampaignsOutput
{
    /** @var list<CampaignSheet> */
    public private(set) array $campaigns = [];

    /** @var array<string, int> the count of contacts each campaign targets, by campaign id */
    public private(set) array $targeted = [];

    /** @var array<string, CampaignLinksResource> by campaign id */
    public private(set) array $links = [];

    public private(set) CampaignListLinksResource $listLinks;

    public function __construct(
        private readonly Links $routes,
        private readonly CampaignPresenter $campaignPresenter,
    ) {
    }

    /**
     * @param list<CampaignSheet> $campaigns
     * @param array<string, int>  $targeted
     */
    public function present(array $campaigns, array $targeted): void
    {
        $this->campaigns = $campaigns;
        $this->targeted = $targeted;

        foreach ($campaigns as $campaign) {
            $this->links[$campaign->id] = $this->campaignPresenter->linksOf($campaign);
        }

        $this->listLinks = new CampaignListLinksResource($this->routes->to('api_campaigns_list'), $this->routes->to('api_campaigns_prepare'));
    }
}
