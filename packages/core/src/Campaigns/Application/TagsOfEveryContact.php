<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The tags of every contact in an account, derived once from the account's partition, as the tag search derives them
 * (ADR 0143). The campaigns find their target among them.
 */
final readonly class TagsOfEveryContact
{
    public function __construct(private TaggedContactsRetriever $taggedContactsRetriever)
    {
    }

    /**
     * @return array<string, ContactTags> by contact id
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
