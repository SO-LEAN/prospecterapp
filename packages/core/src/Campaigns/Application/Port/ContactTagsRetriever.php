<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/** Reads what contacts' tags are derived from, without restoring the taggings or the facts (ADR 0088, 0141). */
interface ContactTagsRetriever
{
    /**
     * @param list<ContactId> $contacts
     *
     * @return list<ContactTagSources> one per contact, in the given order
     */
    public function sourcesOf(AccountId $accountId, array $contacts): array;
}
