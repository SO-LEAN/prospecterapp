<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\HolderKind;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What the tags of contacts derive from, read from the items of `DynamoFactRepository` and `DynamoTaggingRepository`
 * without restoring them (ADR 0088, 0141), by the keys of each contact, never the whole partition: its fact and its
 * links (a `Query` of their range), then the taggings of the contacts and of the organizations their links name, and
 * the facts of those organizations, where they are (ADR 0142), in one `BatchGetItem`.
 */
final readonly class DynamoContactTagsRetriever implements ContactTagsRetriever
{
    public function __construct(private Table $campaignsTable)
    {
    }

    /**
     * @param list<PersonId> $contacts
     */
    public function sourcesOf(AccountId $accountId, array $contacts): array
    {
        $account = CampaignsKeys::account($accountId->value);
        $factsAndLinks = array_map(fn (PersonId $contact): array => $this->campaignsTable->queryBetween($account, null, ...CampaignsKeys::factAndLinksOf($contact->value)), $contacts);
        $organizations = array_values(array_unique(array_merge([], ...array_map(self::organizationsLinked(...), $factsAndLinks))));
        [$tags, $localities] = $this->tagsAndLocalitiesOf($account, $contacts, $organizations);

        return array_map(
            static fn (PersonId $contact, array $items): ContactTagSources => new ContactTagSources(
                $contact->value,
                $tags[HolderKind::Contact->value][$contact->value] ?? [],
                array_map(static fn (string $organization): array => $tags[HolderKind::Organization->value][$organization] ?? [], array_combine(self::organizationsLinked($items), self::organizationsLinked($items))),
                self::reachOf($contact, $items),
                array_intersect_key($localities, array_flip(self::organizationsLinked($items))),
            ),
            $contacts,
            $factsAndLinks,
        );
    }

    /**
     * @param list<array<string, mixed>> $items the fact of a contact and its links
     *
     * @return list<string>
     */
    private static function organizationsLinked(array $items): array
    {
        return Typed::strings(array_column($items, 'organization'));
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array{email: bool, phone: bool, mobile: bool, linkedIn: bool}|null
     */
    private static function reachOf(PersonId $contact, array $items): ?array
    {
        $fact = array_find($items, static fn (array $item): bool => CampaignsKeys::contactFact($contact->value) === $item['SK']);

        return null === $fact ? null : ['email' => (bool) $fact['hasEmail'], 'phone' => (bool) $fact['hasPhone'], 'mobile' => (bool) $fact['hasMobile'], 'linkedIn' => (bool) $fact['hasLinkedIn']];
    }

    /**
     * @param list<PersonId> $contacts
     * @param list<string>   $organizations
     *
     * @return array{array<string, array<string, list<string>>>, array<string, array{municipalityCode: ?string, country: ?string}>}
     *                                                                                                                              the names of the tags put by hand, by kind of holder then by its identity; where each organization is, by its identity
     */
    private function tagsAndLocalitiesOf(string $account, array $contacts, array $organizations): array
    {
        $keys = [
            ...array_map(static fn (PersonId $contact): array => [$account, CampaignsKeys::tagging(HolderKind::Contact->value, $contact->value)], $contacts),
            ...array_map(static fn (string $organization): array => [$account, CampaignsKeys::tagging(HolderKind::Organization->value, $organization)], $organizations),
            ...array_map(static fn (string $organization): array => [$account, CampaignsKeys::organizationFact($organization)], $organizations),
        ];
        $tags = [];
        $localities = [];

        foreach ($this->campaignsTable->items($keys) as $item) {
            if (isset($item['tagging'])) {
                $tagging = Typed::document($item['tagging']);
                $tags[Typed::string($tagging['holder'])][Typed::string($tagging['holderId'])] = Typed::strings($tagging['tags']);
            } else {
                $localities[CampaignsKeys::organizationOfFact(Typed::string($item['SK']))] = OrganizationFactItem::localityIn($item);
            }
        }

        return [$tags, $localities];
    }
}
