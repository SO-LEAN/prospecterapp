<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignListResource;
use App\Campaigning\Resource\CampaignResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\Campaign\ListCampaigns\ListCampaigns;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes the team's campaigns from three contexts, in one batch each. Campaigns keeps the campaigns and counts their
 * targets. Accounts gives each author's person, and PersonalData names them (`CampaignAuthors`). The presenters build
 * the resources (ADR 0085).
 */
final readonly class CampaignReader
{
    private const string NO_CLOCK = 'UTC';

    public function __construct(
        private QueryHandler $consultCampaign,
        private QueryHandler $listCampaigns,
        private CampaignAuthors $campaignAuthors,
        private Links $links,
        private IntentLinks $intentLinks,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param ?string $timeZone the reader's clock, on which the recipients processed today are counted; UTC when null
     */
    public function read(string $reader, string $id, ?string $timeZone = null): CampaignResource
    {
        $presenter = $this->campaignPresenterFor($reader);
        $this->consultCampaign->__invoke(new ConsultCampaign(memberId: $reader, campaignId: $id, timeZone: $timeZone ?? self::NO_CLOCK, at: $this->clock->now()), $presenter);

        return $presenter->resource;
    }

    public function list(string $reader): CampaignListResource
    {
        $presenter = new CampaignListPresenter($this->links, $this->campaignAuthors, $this->campaignPresenterFor($reader), $reader);
        $this->listCampaigns->__invoke(new ListCampaigns($reader), $presenter);

        return $presenter->resource;
    }

    private function campaignPresenterFor(string $reader): CampaignPresenter
    {
        return new CampaignPresenter($this->links, $this->intentLinks, $this->campaignAuthors, $reader);
    }
}
