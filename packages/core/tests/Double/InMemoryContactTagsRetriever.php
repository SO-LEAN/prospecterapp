<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The DynamoDB retriever in the scenarios: the tagging of each contact, the taggings of the organizations whose fact
 * names it, and the reach of its fact, read without their getters.
 */
final readonly class InMemoryContactTagsRetriever implements ContactTagsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    /**
     * @param list<PersonId> $contacts
     */
    public function sourcesOf(AccountId $accountId, array $contacts): array
    {
        return array_map(fn (PersonId $contact): ContactTagSources => $this->sourcesOfOne($accountId, $contact), $contacts);
    }

    private function sourcesOfOne(AccountId $accountId, PersonId $contact): ContactTagSources
    {
        $facts = $this->database->campaignsFactsOf($accountId->value);
        $byOrganization = [];
        $localities = [];

        foreach ($facts as $fact) {
            if ($fact instanceof OrganizationFact && array_any($fact->contacts, $contact->equals(...))) {
                $byOrganization[$fact->id->value] = $this->tagNamesOf($accountId, Holder::organization($fact->id));
                $localities[$fact->id->value] = ['municipalityCode' => $fact->locality->municipalityCode, 'country' => $fact->locality->country];
            }
        }

        $reach = array_find($facts, static fn (object $fact): bool => $fact instanceof ContactFact && $fact->id->equals($contact))?->reach;

        return new ContactTagSources(
            $contact->value,
            $this->tagNamesOf($accountId, Holder::contact($contact)),
            $byOrganization,
            null === $reach ? null : ['email' => $reach->email, 'phone' => $reach->phone, 'mobile' => $reach->mobile, 'linkedIn' => $reach->linkedIn],
            $localities,
        );
    }

    /**
     * @return list<string>
     */
    private function tagNamesOf(AccountId $accountId, Holder $holder): array
    {
        $tagging = $this->database->tagging($accountId->value, $holder);

        return null === $tagging ? [] : array_map(static fn (Tag $tag): string => $tag->name, StateOf::the($tagging)->listOf('tags', Tag::class));
    }
}
