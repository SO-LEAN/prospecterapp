<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the organizations that the in-memory repository saved, with their revision. The adapter
 * reads the table's items in the same way.
 */
final readonly class InMemoryOrganizationFactsRetriever implements OrganizationFactsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(OrganizationId $id): OrganizationFactsResponse
    {
        $organization = $this->database->organization((string) $id);

        return null === $organization ? OrganizationFactsResponse::absent((string) $id) : $this->factsOf($organization);
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $prospected = array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId)));
        $slice = NewestFirst::slice($prospected, static fn (Organization $organization): string => (string) $organization->id(), static fn (Organization $organization): SearchKeys => $organization->searchKeys(), null, $after, $max);

        return new Slice(array_map($this->factsOf(...), $slice->content), $slice->next);
    }

    private function factsOf(Organization $organization): OrganizationFactsResponse
    {
        $state = StateOf::the($organization);

        return new OrganizationFactsResponse(
            (string) $organization->id(),
            $this->database->revisionOf((string) $organization->id()),
            true,
            null !== $state->objectOrNull('geoPoint', GeoPoint::class),
            array_map(static fn (PersonId $contact): string => (string) $contact, $state->listOf('contacts', PersonId::class)),
            $state->stringOrNull('municipalityCode'),
            $state->objectOrNull('address', Address::class)?->country,
        );
    }
}
