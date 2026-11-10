<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the organizations the in-memory repository saved, as the adapter reads the items of the
 * table, their revision with them.
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
        return new OrganizationFactsResponse((string) $organization->id(), $this->database->revisionOf((string) $organization->id()), true, null !== StateOf::the($organization)['geoPoint']);
    }
}
