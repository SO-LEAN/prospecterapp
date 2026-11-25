<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignListLinksResource;
use App\Campaigning\Resource\CampaignListResource;
use App\Campaigning\Resource\CampaignResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Campaign\ListCampaigns\ListCampaignsOutput;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/**
 * Presents the team's campaigns, newest first, each one as the campaign's presenter does, without the count of the
 * reader's day. The list offers to prepare a campaign. The presenter is an object of the call (ADR 0085).
 */
final class CampaignListPresenter implements ListCampaignsOutput
{
    public private(set) CampaignListResource $resource;

    public function __construct(
        private readonly Links $links,
        private readonly CampaignAuthors $campaignAuthors,
        private readonly CampaignPresenter $campaignPresenter,
        private readonly string $reader,
    ) {
    }

    /**
     * @param list<CampaignSheet> $campaigns
     * @param array<string, int>  $targeted
     */
    public function present(array $campaigns, array $targeted): void
    {
        $authors = $this->campaignAuthors->of($this->reader, $campaigns);

        $this->resource = new CampaignListResource(
            array_map(fn (CampaignSheet $campaign): CampaignResource => $this->campaignPresenter->resourceOf($campaign, $targeted[$campaign->id] ?? 0, $authors[$campaign->authorId] ?? null), $campaigns),
            new CampaignListLinksResource($this->links->to('api_campaigns_list'), $this->links->to('api_campaigns_prepare')),
        );
    }
}
