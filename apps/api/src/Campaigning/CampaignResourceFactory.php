<?php

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignLinksResource;
use App\Campaigning\Resource\CampaignResource;
use App\Exchanges\Resource\AuthorResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * A campaign as the API answers it, its links those its state offers, every one needing the right to manage personal
 * data (ADR 0081, 0144); its target, the search by its targeted tags.
 */
final readonly class CampaignResourceFactory
{
    /**
     * @var array<string, array{intent: string, route: string}>
     */
    private const array ROUTES = [
        'revise' => ['intent' => 'ReviseCampaign', 'route' => 'api_campaigns_revise'],
        'discard' => ['intent' => 'DiscardCampaign', 'route' => 'api_campaigns_discard'],
    ];
    private const string TAG_MARK = '#';

    public function __construct(private Links $links, private IntentLinks $intentLinks)
    {
    }

    public function of(CampaignSheet $campaign, int $targeted, ?PersonCard $author): CampaignResource
    {
        return new CampaignResource(
            id: $campaign->id,
            name: $campaign->name,
            channel: $campaign->channel,
            tags: $campaign->tags,
            targetedTags: $campaign->targetedTags,
            message: $campaign->message,
            subject: $campaign->subject,
            status: $campaign->status,
            author: new AuthorResource(new PersonNameResource($author?->firstName, $author?->lastName), $author?->email),
            targeted: $targeted,
            links: $this->linksOf($campaign),
        );
    }

    private function linksOf(CampaignSheet $campaign): CampaignLinksResource
    {
        $id = ['id' => $campaign->id];
        $granted = array_filter(self::ROUTES, fn (array $link): bool => $this->intentLinks->shows($link['intent'], $campaign->possibleIntents, User::PERSONAL_DATA));

        return new CampaignLinksResource(
            $this->links->to('api_campaigns_show', $id),
            ...[
                ...array_map(fn (array $link): LinkResource => $this->links->to($link['route'], $id), $granted),
                'target' => $this->links->to('api_contacts_search_by_tags', ['tags' => implode(',', array_map(self::nameOf(...), $campaign->targetedTags))]),
            ],
        );
    }

    private static function nameOf(string $tag): string
    {
        return ltrim($tag, self::TAG_MARK);
    }
}
