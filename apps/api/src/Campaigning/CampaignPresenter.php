<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignLinksResource;
use App\Campaigning\Resource\CampaignProgressResource;
use App\Campaigning\Resource\CampaignResource;
use App\Exchanges\Resource\AuthorResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaignOutput;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Presents a campaign as the API returns it (ADR 0081, 0144). Its links are those its state allows, and each one needs
 * the right to manage personal data. A draft links to its target, the search by its targeted tags. A frozen campaign
 * links to its recipients. The presenter is an object of the call (ADR 0085). The list of the campaigns presents each
 * one with it.
 */
final class CampaignPresenter implements ConsultCampaignOutput
{
    private const string TAG_MARK = '#';

    public private(set) CampaignResource $resource;

    public function __construct(
        private readonly Links $links,
        private readonly IntentLinks $intentLinks,
        private readonly CampaignAuthors $campaignAuthors,
        private readonly string $reader,
    ) {
    }

    public function present(CampaignSheet $campaign, int $targeted, int $processedToday): void
    {
        $author = $this->campaignAuthors->of($this->reader, [$campaign])[$campaign->authorId] ?? null;

        $this->resource = $this->resourceOf($campaign, $targeted, $author, $processedToday);
    }

    /**
     * @param ?int $processedToday its recipients processed on the reader's day, set only when the campaign is read alone
     */
    public function resourceOf(CampaignSheet $campaign, int $targeted, ?PersonCard $author, ?int $processedToday = null): CampaignResource
    {
        return new CampaignResource(
            id: $campaign->id,
            name: $campaign->name,
            channel: $campaign->channel,
            tags: $campaign->tags,
            targetedTags: $campaign->targetedTags,
            message: $campaign->message,
            subject: $campaign->subject,
            messageMode: $campaign->messageMode,
            status: $campaign->status,
            author: new AuthorResource(new PersonNameResource($author?->firstName, $author?->lastName), $author?->email),
            progress: new CampaignProgressResource($campaign->processed, $campaign->dismissed),
            targeted: $targeted,
            startedAt: $campaign->startedAt?->format(\DateTimeInterface::ATOM),
            stoppedAt: $campaign->stoppedAt?->format(\DateTimeInterface::ATOM),
            endedAt: $campaign->endedAt?->format(\DateTimeInterface::ATOM),
            dailyCap: $campaign->dailyCap,
            processedToday: $processedToday,
            links: $this->linksOf($campaign),
        );
    }

    private function linksOf(CampaignSheet $campaign): CampaignLinksResource
    {
        return new CampaignLinksResource(
            self: $this->links->to('api_campaigns_show', ['id' => $campaign->id]),
            revise: $this->offered($campaign, 'ReviseCampaign', 'api_campaigns_revise'),
            discard: $this->offered($campaign, 'DiscardCampaign', 'api_campaigns_discard'),
            freeze: $this->offered($campaign, 'FreezeCampaign', 'api_campaigns_freeze'),
            addRecipient: $this->offered($campaign, 'AddRecipients', 'api_campaigns_add_recipient'),
            addRecipientsByTags: $this->offered($campaign, 'AddRecipients', 'api_campaigns_add_recipients_by_tags'),
            start: $this->offered($campaign, 'StartCampaign', 'api_campaigns_start'),
            stop: $this->offered($campaign, 'StopCampaign', 'api_campaigns_stop'),
            setDailyCap: $this->offered($campaign, 'SetDailyCap', 'api_campaigns_set_daily_cap'),
            target: $campaign->followsTheTags ? $this->links->to('api_contacts_search_by_tags', ['tags' => implode(',', array_map(self::nameOf(...), $campaign->targetedTags))]) : null,
            recipients: $campaign->followsTheTags ? null : $this->links->to('api_campaigns_recipients', ['id' => $campaign->id]),
        );
    }

    /**
     * The link of an intent the campaign's state offers now, to a member who manages personal data.
     */
    private function offered(CampaignSheet $campaign, string $intent, string $route): ?LinkResource
    {
        return $this->intentLinks->shows($intent, $campaign->possibleIntents, User::PERSONAL_DATA) ? $this->links->to($route, ['id' => $campaign->id]) : null;
    }

    private static function nameOf(string $tag): string
    {
        return ltrim($tag, self::TAG_MARK);
    }
}
