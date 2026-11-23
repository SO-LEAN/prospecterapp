<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads at once what the tags of every contact in an account are derived from, to search the contacts by tags (ADR
 * 0088, 0141, 0143).
 */
interface TaggedContactsRetriever
{
    /**
     * @return list<ContactTagSources> one per contact whose fact says it is kept, in no particular order
     */
    public function sourcesOfEveryContact(AccountId $accountId): array;
}
