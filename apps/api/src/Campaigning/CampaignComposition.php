<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignLinksResource;
use App\Campaigning\Resource\CampaignListResource;
use App\Campaigning\Resource\CampaignProgressResource;
use App\Campaigning\Resource\CampaignResource;
use App\Contacts\PersonsOfAView;
use App\Exchanges\Resource\AuthorResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\Campaign\ListCampaigns\ListCampaigns;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes the team's campaigns (ADR 0217). Campaigns keeps them, counts their targets and links them. Accounts and
 * PersonalData name their authors (`PersonsOfAView`), joined by the authors' member ids. A command returns nothing, so
 * a controller reads the campaign again after it to answer with the fresh resource.
 */
final readonly class CampaignComposition
{
    private const string NO_CLOCK = 'UTC';

    public function __construct(
        private QueryHandler $consultCampaign,
        private QueryHandler $listCampaigns,
        private PersonsOfAView $personsOfAView,
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
        $presenter = new CampaignPresenter($this->links, $this->intentLinks);
        $this->consultCampaign->__invoke(new ConsultCampaign(memberId: $reader, campaignId: $id, timeZone: $timeZone ?? self::NO_CLOCK, at: $this->clock->now()), $presenter);
        $campaign = $presenter->campaign;
        $authors = $this->personsOfAView->shownTo($reader, [], [$campaign->authorId])->members;

        return self::resourceOf($campaign, $presenter->targeted, $authors[$campaign->authorId] ?? null, $presenter->links, $presenter->processedToday);
    }

    public function list(string $reader): CampaignListResource
    {
        $presenter = new CampaignListPresenter($this->links, new CampaignPresenter($this->links, $this->intentLinks));
        $this->listCampaigns->__invoke(new ListCampaigns($reader), $presenter);
        $authors = $this->personsOfAView->shownTo($reader, [], array_map(static fn (CampaignSheet $campaign): string => $campaign->authorId, $presenter->campaigns))->members;

        return new CampaignListResource(
            array_map(static fn (CampaignSheet $campaign): CampaignResource => self::resourceOf($campaign, $presenter->targeted[$campaign->id] ?? 0, $authors[$campaign->authorId] ?? null, $presenter->links[$campaign->id]), $presenter->campaigns),
            $presenter->listLinks,
        );
    }

    /**
     * @param ?int $processedToday its recipients processed on the reader's day, set only when the campaign is read alone
     */
    private static function resourceOf(CampaignSheet $campaign, int $targeted, ?PersonCard $author, CampaignLinksResource $links, ?int $processedToday = null): CampaignResource
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
            links: $links,
        );
    }
}
