<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The tags of every contact of an account, derived once from the partition of the account, as the search by tags
 * derives them (ADR 0143), for the campaigns to find their target in them.
 */
final readonly class TagsOfEveryContact
{
    public function __construct(private TaggedContactsRetriever $taggedContactsRetriever)
    {
    }

    /**
     * @return array<string, ContactTags> by contact identity
     */
    #[\NoDiscard]
    public function of(AccountId $accountId): array
    {
        $tags = [];

        foreach ($this->taggedContactsRetriever->sourcesOfEveryContact($accountId) as $contact) {
            $tags[$contact->contactId] = $contact->tags();
        }

        return $tags;
    }
}
