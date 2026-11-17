<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads what the tags of every contact of an account are derived from, at once, to search them by their tags
 * (ADR 0088, 0141, 0143).
 */
interface TaggedContactsRetriever
{
    /**
     * @return list<ContactTagSources> one per contact whose fact says it is kept, in no order
     */
    public function sourcesOfEveryContact(AccountId $accountId): array;
}
