<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads what the tags of contacts are derived from, without restoring the taggings nor the facts (ADR 0088, 0141).
 */
interface ContactTagsRetriever
{
    /**
     * @param list<PersonId> $contacts
     *
     * @return list<ContactTagSources> one per contact, in their order
     */
    public function sourcesOf(AccountId $accountId, array $contacts): array;
}
