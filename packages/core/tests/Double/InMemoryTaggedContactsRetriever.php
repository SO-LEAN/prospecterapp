<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The DynamoDB retriever in the scenarios. It reads every contact of the account whose fact says it is kept, with what
 * its tags are derived from, as it reads one contact.
 */
final readonly class InMemoryTaggedContactsRetriever implements TaggedContactsRetriever
{
    public function __construct(private InMemoryDatabase $database, private ContactTagsRetriever $contactTagsRetriever)
    {
    }

    public function sourcesOfEveryContact(AccountId $accountId): array
    {
        $kept = array_filter($this->database->campaignsFactsOf($accountId->value), static fn (object $fact): bool => $fact instanceof ContactFact && $fact->present);

        return $this->contactTagsRetriever->sourcesOf($accountId, array_values(array_map(static fn (ContactFact $fact): ContactId => $fact->id, $kept)));
    }
}
