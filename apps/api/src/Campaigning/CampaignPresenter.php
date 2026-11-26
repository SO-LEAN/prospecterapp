<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaignOutput;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/**
 * Presents what Campaigns shows of a campaign (ADR 0081, 0144): the campaign and its links. Its links are those its
 * state allows, and each one needs the right to manage personal data. A draft links to its target, the search by its
 * targeted tags. A frozen campaign links to its recipients. The presenter is an object of the call (ADR 0085). The list
 * of the campaigns links each one with it.
 */
final class CampaignPresenter implements ConsultCampaignOutput
{
    private const string TAG_MARK = '#';

    public private(set) CampaignSheet $campaign;

    public private(set) int $targeted;

    public private(set) int $processedToday;

    public private(set) CampaignLinksResource $links;

    public function __construct(
        private readonly Links $routes,
        private readonly IntentLinks $intentLinks,
    ) {
    }

    public function present(CampaignSheet $campaign, int $targeted, int $processedToday): void
    {
        $this->campaign = $campaign;
        $this->targeted = $targeted;
        $this->processedToday = $processedToday;
        $this->links = $this->linksOf($campaign);
    }

    public function linksOf(CampaignSheet $campaign): CampaignLinksResource
    {
        return new CampaignLinksResource(
            self: $this->routes->to('api_campaigns_show', ['id' => $campaign->id]),
            revise: $this->offered($campaign, 'ReviseCampaign', 'api_campaigns_revise'),
            discard: $this->offered($campaign, 'DiscardCampaign', 'api_campaigns_discard'),
            freeze: $this->offered($campaign, 'FreezeCampaign', 'api_campaigns_freeze'),
            addRecipient: $this->offered($campaign, 'AddRecipients', 'api_campaigns_add_recipient'),
            addRecipientsByTags: $this->offered($campaign, 'AddRecipients', 'api_campaigns_add_recipients_by_tags'),
            start: $this->offered($campaign, 'StartCampaign', 'api_campaigns_start'),
            stop: $this->offered($campaign, 'StopCampaign', 'api_campaigns_stop'),
            setDailyCap: $this->offered($campaign, 'SetDailyCap', 'api_campaigns_set_daily_cap'),
            target: $campaign->followsTheTags ? $this->routes->to('api_contacts_search_by_tags', ['tags' => implode(',', array_map(self::nameOf(...), $campaign->targetedTags))]) : null,
            recipients: $campaign->followsTheTags ? null : $this->routes->to('api_campaigns_recipients', ['id' => $campaign->id]),
        );
    }

    /**
     * The link of an intent the campaign's state offers now, to a member who manages personal data.
     */
    private function offered(CampaignSheet $campaign, string $intent, string $route): ?LinkResource
    {
        return $this->intentLinks->shows($intent, $campaign->possibleIntents, User::PERSONAL_DATA) ? $this->routes->to($route, ['id' => $campaign->id]) : null;
    }

    private static function nameOf(string $tag): string
    {
        return ltrim($tag, self::TAG_MARK);
    }
}
