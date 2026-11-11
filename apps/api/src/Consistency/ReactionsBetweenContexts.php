<?php

namespace App\Consistency;

/**
 * What the reactions between the contexts should have written, read in the tables: each account has a member at
 * least, its founder registered, and each member their profile in PersonalData; each contact collected is attached to
 * organizations, none forgotten still is; no organization no longer prospected still keeps exchanges.
 */
final readonly class ReactionsBetweenContexts
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
        return [
            ...$this->membersMissingAmong($accountIds),
            ...array_merge(...array_map($this->contactsAndExchangesOf(...), $accountIds)),
        ];
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    private function membersMissingAmong(array $accountIds): array
    {
        $members = array_values(array_filter($this->tables->members(), static fn (array $member): bool => in_array($member['accountId'], $accountIds, true)));
        $withMembers = array_column($members, 'accountId', 'accountId');

        return [
            ...array_map(self::withoutMember(...), array_values(array_diff($accountIds, $withMembers))),
            ...$this->membersWithoutProfileAmong($members),
        ];
    }

    private static function withoutMember(string $accountId): Discrepancy
    {
        return new Discrepancy($accountId, $accountId, 'account without member: its founder is not registered', Correction::GiveTheReactionAgain);
    }

    /**
     * @param list<array{id: string, accountId: string, personId: string}> $members
     *
     * @return list<Discrepancy>
     */
    private function membersWithoutProfileAmong(array $members): array
    {
        $profiles = array_flip($this->tables->keptPersonsAmong(array_column($members, 'personId')));
        $withoutProfile = array_filter($members, static fn (array $member): bool => !isset($profiles[$member['personId']]));

        return array_values(array_map(static fn (array $member): Discrepancy => new Discrepancy($member['accountId'], $member['id'], 'member without their profile '.$member['personId'], Correction::GiveTheReactionAgain), $withoutProfile));
    }

    /**
     * @return list<Discrepancy>
     */
    private function contactsAndExchangesOf(string $accountId): array
    {
        $organizations = $this->tables->organizationsOf($accountId);
        $contacts = $this->tables->contactsOf($accountId);

        return [
            ...$this->contactsNotAttached($accountId, $contacts, $organizations),
            ...$this->forgottenContactsAttached($accountId, $contacts, $organizations),
            ...$this->exchangesOfOrganizationsGone($accountId, $organizations),
        ];
    }

    /**
     * @param list<string>               $contacts
     * @param list<array<string, mixed>> $organizations
     *
     * @return list<Discrepancy>
     */
    private function contactsNotAttached(string $accountId, array $contacts, array $organizations): array
    {
        $notAttached = array_values(array_diff($contacts, ...array_column($organizations, 'contacts')));

        return array_map(static fn (string $contact): Discrepancy => new Discrepancy($accountId, $contact, 'contact attached to no organization', Correction::GiveTheReactionAgain), $notAttached);
    }

    /**
     * @param list<string>               $contacts
     * @param list<array<string, mixed>> $organizations
     *
     * @return list<Discrepancy>
     */
    private function forgottenContactsAttached(string $accountId, array $contacts, array $organizations): array
    {
        $discrepancies = [];

        foreach ($organizations as $organization) {
            foreach (array_diff($organization['contacts'], $contacts) as $forgotten) {
                $discrepancies[] = new Discrepancy($accountId, $forgotten, 'forgotten contact still attached to '.$organization['id'], Correction::GiveTheReactionAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param list<array<string, mixed>> $organizations
     *
     * @return list<Discrepancy>
     */
    private function exchangesOfOrganizationsGone(string $accountId, array $organizations): array
    {
        $prospected = array_column($organizations, 'id');
        $discrepancies = [];

        foreach ($this->tables->exchangesOf($accountId) as $exchange) {
            foreach (array_diff($exchange['keptFor'], $prospected) as $gone) {
                $discrepancies[] = new Discrepancy($accountId, $exchange['id'], sprintf('exchange kept for %s, no longer prospected', $gone), Correction::GiveTheReactionAgain);
            }

            if ([] === $exchange['keptFor']) {
                $discrepancies[] = new Discrepancy($accountId, $exchange['id'], 'exchange kept for no organization, not erased', Correction::GiveTheReactionAgain);
            }
        }

        return $discrepancies;
    }
}
