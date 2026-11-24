<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\TagsOfEveryContact;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Counts the contacts each campaign targets (ADR 0144). A draft targets the contacts that carry its tags now. Every
 * contact's tags are derived once for all the campaigns read. A frozen campaign targets its recipients.
 */
final readonly class CampaignTargets
{
    public function __construct(private TagsOfEveryContact $tagsOfEveryContact)
    {
    }

    /**
     * @param list<CampaignSheet> $campaigns the account's campaigns
     *
     * @return array<string, int> by campaign id
     */
    #[\NoDiscard]
    public function counted(AccountId $accountId, array $campaigns): array
    {
        $contacts = array_any($campaigns, static fn (CampaignSheet $campaign): bool => $campaign->followsTheTags) ? $this->tagsOfEveryContact->of($accountId) : [];
        $counted = [];

        foreach ($campaigns as $campaign) {
            $counted[$campaign->id] = $campaign->followsTheTags ? self::carryingAll($campaign->targetedTags, $contacts) : count($campaign->recipients);
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
