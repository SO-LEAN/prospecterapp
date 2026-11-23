<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/** Reads what contacts' tags are derived from, without restoring the taggings or the facts (ADR 0088, 0141). */
interface ContactTagsRetriever
{
    /**
     * @param list<PersonId> $contacts
     *
     * @return list<ContactTagSources> one per contact, in the given order
     */
    public function sourcesOf(AccountId $accountId, array $contacts): array;
}
