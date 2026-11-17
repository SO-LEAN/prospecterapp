<?php

namespace App\Consistency;

use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\Documents;

/**
 * The facts the tags of the contacts derive from, kept by Campaigns, against the tables of Prospecting and PersonalData
 * (ADR 0141): an organization whose contacts or whose locality (ADR 0142), or a contact whose reach, Campaigns lacks or
 * keeps out of date is corrected by a rebuild of the tags of its account; a fact kept for what is gone, a terminal fact
 * missed, by the reaction given again.
 */
final readonly class TagsAgainstTheTables
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
        $organizationDocuments = $this->tables->organizationsOf($accountId);
        $organizations = array_column($organizationDocuments, 'contacts', 'id');
        $localities = array_combine(array_column($organizationDocuments, 'id'), array_map(self::localityOf(...), $organizationDocuments));
        $persons = $this->tables->contactDocumentsOf($accountId);
        $contacts = array_combine(array_column($persons, 'id'), array_map(self::reachOf(...), $persons));
        $organizationFacts = $this->tables->organizationFactsOf($accountId);
        $contactFacts = $this->tables->contactFactsOf($accountId);

        return [
            ...self::outOfDate($accountId, $organizations, $organizationFacts, self::sorted(...), 'contacts of the organization out of date in Campaigns'),
            ...self::outOfDate($accountId, $localities, $this->tables->organizationLocalitiesOf($accountId), static fn (array $locality): array => $locality, 'locality of the organization out of date in Campaigns'),
            ...self::keptForWhatIsGone($accountId, $organizations, $organizationFacts, 'facts kept in Campaigns for an organization no longer prospected'),
            ...self::outOfDate($accountId, $contacts, $contactFacts, static fn (array $reach): array => $reach, 'reach of the contact out of date in Campaigns'),
            ...self::keptForWhatIsGone($accountId, $contacts, $contactFacts, 'facts kept in Campaigns for a forgotten contact'),
        ];
    }

    /**
     * @param array<string, array<mixed>>          $state    what the table of the context keeps, by id
     * @param array<string, array<mixed>>          $facts    what Campaigns keeps, by id
     * @param \Closure(array<mixed>): array<mixed> $compared
     *
     * @return list<Discrepancy>
     */
    private static function outOfDate(string $accountId, array $state, array $facts, \Closure $compared, string $description): array
    {
        $outOfDate = array_filter(array_keys($state), static fn (string|int $id): bool => !isset($facts[$id]) || $compared($facts[$id]) !== $compared($state[$id]));

        return array_values(array_map(static fn (string|int $id): Discrepancy => new Discrepancy($accountId, (string) $id, $description, Correction::RebuildTheTags), $outOfDate));
    }

    /**
     * @param array<string, array<mixed>> $state
     * @param array<string, array<mixed>> $facts
     *
     * @return list<Discrepancy>
     */
    private static function keptForWhatIsGone(string $accountId, array $state, array $facts, string $description): array
    {
        return array_map(static fn (string|int $id): Discrepancy => new Discrepancy($accountId, (string) $id, $description, Correction::GiveTheReactionAgain), array_keys(array_diff_key($facts, $state)));
    }

    /**
     * @param array<string, mixed> $organization
     *
     * @return array{municipalityCode: ?string, country: ?string}
     */
    private static function localityOf(array $organization): array
    {
        return ['municipalityCode' => $organization['municipalityCode'] ?? null, 'country' => $organization['address']['country'] ?? null];
    }

    /**
     * @param array<string, mixed> $person
     *
     * @return array{hasEmail: bool, hasPhone: bool, hasMobile: bool, hasLinkedIn: bool}
     */
    private static function reachOf(array $person): array
    {
        $contactDetails = Documents::contactDetailsOf($person['contactDetails']);

        return [
            'hasEmail' => null !== $contactDetails->email,
            'hasPhone' => null !== $contactDetails->phoneNumber,
            'hasMobile' => true === $contactDetails->phoneNumber?->isMobile(),
            'hasLinkedIn' => null !== PersonDocument::linkedInProfileOf($person),
        ];
    }

    /**
     * @param list<string> $contacts
     *
     * @return list<string>
     */
    private static function sorted(array $contacts): array
    {
        sort($contacts);

        return $contacts;
    }
}
