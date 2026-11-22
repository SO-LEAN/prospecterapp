<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\HolderKind;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * What the tags of every contact of an account derive from, read from the items of `DynamoFactRepository` and
 * `DynamoTaggingRepository` without restoring them (ADR 0088, 0141): the partition of the account at once, the facts
 * of the contacts each followed by its links, its taggings, then the facts of its organizations, where they are (ADR
 * 0142), three `Query`.
 */
final readonly class DynamoTaggedContactsRetriever implements TaggedContactsRetriever
{
    public function __construct(private Table $campaignsTable)
    {
    }

    /**
     * @return list<ContactTagSources>
     */
    public function sourcesOfEveryContact(AccountId $accountId): array
    {
        $account = CampaignsKeys::account($accountId->value);
        [$facts, $links] = self::factsAndLinksOf($this->campaignsTable->queryBetween($account, null, ...CampaignsKeys::contactFacts()));
        $tags = $this->tagsIn($account);
        $localities = $this->localitiesIn($account);
        $sources = [];

        foreach ($facts as $contact => $fact) {
            $organizations = $links[$contact] ?? [];
            $sources[] = new ContactTagSources(
                $contact,
                $tags[HolderKind::Contact->value][$contact] ?? [],
                array_combine($organizations, array_map(static fn (string $organization): array => $tags[HolderKind::Organization->value][$organization] ?? [], $organizations)),
                ['email' => (bool) $fact['hasEmail'], 'phone' => (bool) $fact['hasPhone'], 'mobile' => (bool) $fact['hasMobile'], 'linkedIn' => (bool) $fact['hasLinkedIn']],
                array_intersect_key($localities, array_flip($organizations)),
            );
        }

        return $sources;
    }

    /**
     * @param list<array<string, mixed>> $items the facts of the contacts and their links
     *
     * @return array{array<string, array<string, mixed>>, array<string, list<string>>} the fact of each contact kept,
     *                                                                                 and the organizations linked to
     *                                                                                 each contact, by its identity
     */
    private static function factsAndLinksOf(array $items): array
    {
        $facts = [];
        $links = [];

        foreach ($items as $item) {
            $contact = self::contactOf((string) $item['SK']);

            if (isset($item['organization'])) {
                $links[$contact][] = (string) $item['organization'];
            } elseif ((bool) $item['present']) {
                $facts[$contact] = $item;
            }
        }

        return [$facts, $links];
    }

    /**
     * @param string $key the key of the fact of a contact, or of one of its links
     */
    private static function contactOf(string $key): string
    {
        return explode('#', substr($key, strlen(CampaignsKeys::contactFact(''))), 2)[0];
    }

    /**
     * @return array<string, array{municipalityCode: ?string, country: ?string}> where each organization is, by its identity
     */
    private function localitiesIn(string $account): array
    {
        $localities = [];

        foreach ($this->campaignsTable->queryBetween($account, null, ...CampaignsKeys::organizationFacts()) as $item) {
            $localities[CampaignsKeys::organizationOfFact((string) $item['SK'])] = OrganizationFactItem::localityIn($item);
        }

        return $localities;
    }

    /**
     * @return array<string, array<string, list<string>>> the names of the tags put by hand, by kind of holder then by its identity
     */
    private function tagsIn(string $account): array
    {
        $tags = [];

        foreach ($this->campaignsTable->queryBetween($account, null, ...CampaignsKeys::taggings()) as $item) {
            $tags[$item['tagging']['holder']][$item['tagging']['holderId']] = $item['tagging']['tags'];
        }

        return $tags;
    }
}
