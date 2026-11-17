<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Counts the contacts each campaign targets as their tags are now (ADR 0144): the tags of every contact of the
 * account derived once, as the search by tags does (ADR 0143), for all the campaigns read.
 */
final readonly class CampaignTargets
{
    public function __construct(private TaggedContactsRetriever $taggedContactsRetriever)
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
        if ([] === $campaigns) {
            return [];
        }

        $contacts = array_map(static fn (ContactTagSources $contact): ContactTags => $contact->tags(), $this->taggedContactsRetriever->sourcesOfEveryContact($accountId));
        $counted = [];

        foreach ($campaigns as $campaign) {
            $targeted = TagSelection::of($campaign->targetedTags);
            $counted[$campaign->id] = count(array_filter($contacts, static fn (ContactTags $tags): bool => $tags->carriesAll($targeted)));
        }

        return $counted;
    }
}
