<?php

namespace App\Consistency;

/**
 * A LinkedIn profile designates one contact of an account, by their profile or a former one: two contacts noting the
 * same, noted before it was unique, are shown by their ids, never the profile, for the members to merge them or correct
 * one of them by hand.
 */
final readonly class LinkedInProfilesOfTheContacts
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
            foreach (self::profilesHeldBy($contact) as $profile) {
                $holders[$profile][] = $contact['id'];
            }
        }
        $shared = array_filter($holders, static fn (array $contacts): bool => count($contacts) > 1);

        return array_values(array_map(static fn (array $contacts): Discrepancy => new Discrepancy($accountId, implode(', ', self::sorted($contacts)), 'contacts noting the same LinkedIn profile', Correction::MergeOrCorrectByHand), $shared));
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
     * @return list<string>
     */
    private static function profilesHeldBy(array $contact): array
    {
        return array_values(array_filter([$contact['linkedInProfile'] ?? null, ...$contact['formerLinkedInProfiles'] ?? []]));
    }
}
