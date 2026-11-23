<?php

declare(strict_types=1);

namespace App\Consistency;

use App\Prospecting\Persistence\ContactToAttachDocument;
use App\Shared\Typed;
use Symfony\Component\Clock\ClockInterface;

/**
 * What the reactions between the contexts should have written, read in the tables. Each account has at least one
 * member, its registered founder. Each member has their profile in PersonalData. Each collected contact is attached to
 * organizations, and no forgotten contact still is. No organization that is no longer prospected keeps exchanges or
 * tags. No campaign keeps a forgotten contact among its recipients. A contact to attach is attached to no organization,
 * and their exchanges are kept for none, until the day of their erasure (ADR 0146).
 */
final readonly class ReactionsBetweenContexts
{
    private const string ORGANIZATION = 'organization';
    private const string CONTACT = 'contact';
    private const array TAGS_KEPT_FOR = [
        self::ORGANIZATION => 'tags kept for an organization no longer prospected',
        self::CONTACT => 'tags kept for a forgotten contact',
    ];

    public function __construct(private ContextTables $tables, private ClockInterface $clock)
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
        $toAttach = $this->tables->contactsToAttachOf($accountId);
        $contactsToAttach = Typed::strings(array_column($toAttach, 'id'));

        return [
            ...$this->contactsNotAttached($accountId, array_values(array_diff($contacts, $contactsToAttach)), $organizations),
            ...$this->forgottenContactsAttached($accountId, $contacts, $organizations),
            ...$this->exchangesOfOrganizationsGone($accountId, $organizations, $contactsToAttach),
            ...$this->contactsToAttachPastTheirDay($accountId, $toAttach),
            ...$this->tagsOfWhatIsGone($accountId, $contacts, $organizations),
            ...$this->recipientsForgotten($accountId, $contacts),
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
        $notAttached = array_values(array_diff($contacts, ...array_map(static fn (array $organization): array => Typed::strings($organization['contacts']), $organizations)));

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
            foreach (array_diff(Typed::strings($organization['contacts']), $contacts) as $forgotten) {
                $discrepancies[] = new Discrepancy($accountId, $forgotten, 'forgotten contact still attached to '.Typed::string($organization['id']), Correction::GiveTheReactionAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param list<array<string, mixed>> $toAttach
     *
     * @return list<Discrepancy>
     */
    private function contactsToAttachPastTheirDay(string $accountId, array $toAttach): array
    {
        $now = $this->clock->now();
        $past = array_filter($toAttach, static fn (array $document): bool => ContactToAttachDocument::erasableFrom($document) <= $now);

        return array_values(array_map(static fn (array $document): Discrepancy => new Discrepancy($accountId, Typed::string($document['id']), 'contact to attach past the day of their erasure, not erased', Correction::EraseTheUnattached), $past));
    }

    /**
     * @param list<array<string, mixed>> $organizations
     * @param list<string>               $contactsToAttach the contacts whose exchanges are kept for none
     *
     * @return list<Discrepancy>
     */
    private function exchangesOfOrganizationsGone(string $accountId, array $organizations, array $contactsToAttach): array
    {
        $prospected = Typed::strings(array_column($organizations, 'id'));
        $discrepancies = [];

        foreach ($this->tables->exchangesOf($accountId) as $exchange) {
            $id = Typed::string($exchange['id']);
            $keptFor = Typed::strings($exchange['keptFor']);

            foreach (array_diff($keptFor, $prospected) as $gone) {
                $discrepancies[] = new Discrepancy($accountId, $id, sprintf('exchange kept for %s, no longer prospected', $gone), Correction::GiveTheReactionAgain);
            }

            if ([] === $keptFor && !in_array($exchange['contact'], $contactsToAttach, true)) {
                $discrepancies[] = new Discrepancy($accountId, $id, 'exchange kept for no organization, not erased', Correction::GiveTheReactionAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param list<string> $contacts
     *
     * @return list<Discrepancy>
     */
    private function recipientsForgotten(string $accountId, array $contacts): array
    {
        $discrepancies = [];

        foreach ($this->tables->recipientsOf($accountId) as $campaign => $recipients) {
            foreach (array_diff($recipients, $contacts) as $forgotten) {
                $discrepancies[] = new Discrepancy($accountId, (string) $campaign, 'recipient kept for a forgotten contact '.$forgotten, Correction::GiveTheReactionAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param list<string>               $contacts
     * @param list<array<string, mixed>> $organizations
     *
     * @return list<Discrepancy>
     */
    private function tagsOfWhatIsGone(string $accountId, array $contacts, array $organizations): array
    {
        $kept = [self::ORGANIZATION => array_column($organizations, 'id'), self::CONTACT => $contacts];
        $gone = array_filter($this->tables->taggingsOf($accountId), static fn (array $tagging): bool => !in_array($tagging['holderId'], $kept[$tagging['holder']] ?? [], true));

        return array_values(array_map(static fn (array $tagging): Discrepancy => new Discrepancy($accountId, $tagging['holderId'], self::TAGS_KEPT_FOR[$tagging['holder']], Correction::GiveTheReactionAgain), $gone));
    }
}
