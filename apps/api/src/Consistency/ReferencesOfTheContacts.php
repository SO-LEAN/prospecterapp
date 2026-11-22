<?php

declare(strict_types=1);

namespace App\Consistency;

use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Typed;

/**
 * A link designates one contact of an account, by their links or their former ones: two contacts noting the same,
 * noted before it was unique, are shown by their ids, never the link, for the members to merge them or correct one of
 * them by hand.
 */
final readonly class ReferencesOfTheContacts
{
    public function __construct(private ContextTables $tables)
    {
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    public function discrepanciesAmong(array $accountIds): array
    {
        return array_merge([], ...array_map($this->discrepanciesOf(...), $accountIds));
    }

    /**
     * @return list<Discrepancy>
     */
    private function discrepanciesOf(string $accountId): array
    {
        $holders = [];

        foreach ($this->tables->contactDocumentsOf($accountId) as $contact) {
            foreach (self::referencesHeldBy($contact) as $reference) {
                $holders[$reference][] = Typed::string($contact['id']);
            }
        }
        $shared = array_filter($holders, static fn (array $contacts): bool => count($contacts) > 1);

        return array_values(array_map(static fn (array $contacts): Discrepancy => new Discrepancy($accountId, implode(', ', self::sorted($contacts)), 'contacts noting the same link', Correction::MergeOrCorrectByHand), $shared));
    }

    /**
     * @param list<string> $contacts
     *
     * @return list<string> the oldest first
     */
    private static function sorted(array $contacts): array
    {
        sort($contacts);

        return $contacts;
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return list<string> what recognizes each
     */
    private static function referencesHeldBy(array $contact): array
    {
        return PersonDocument::referenceKeysHeld($contact);
    }
}
