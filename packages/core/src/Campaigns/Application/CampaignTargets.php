<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Counts the contacts each campaign targets (ADR 0144): a draft, those carrying its tags as they are now, the tags of
 * every contact of the account derived once for all the campaigns read; a frozen one, its recipients.
 */
final readonly class CampaignTargets
{
    public function __construct(private TagsOfEveryContact $tagsOfEveryContact)
    {
    }

    /**
     * @param list<CampaignSheet> $campaigns of the account
     *
     * @return array<string, int> by campaign identity
     */
    #[\NoDiscard]
    public function counted(AccountId $accountId, array $campaigns): array
    {
        $drafts = array_filter($campaigns, static fn (CampaignSheet $campaign): bool => CampaignStatus::from($campaign->status)->followsTheTags());
        $contacts = [] === $drafts ? [] : $this->tagsOfEveryContact->of($accountId);
        $counted = [];

        foreach ($campaigns as $campaign) {
            $counted[$campaign->id] = in_array($campaign, $drafts, true) ? self::carryingAll($campaign->targetedTags, $contacts) : count($campaign->recipients);
        }

        return $counted;
    }

    /**
     * @param list<string>               $tags
     * @param array<string, ContactTags> $contacts
     */
    private static function carryingAll(array $tags, array $contacts): int
    {
        $targeted = TagSelection::of($tags);

        return count(array_filter($contacts, static fn (ContactTags $contact): bool => $contact->carriesAll($targeted)));
    }
}
