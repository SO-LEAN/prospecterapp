<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\SearchProjection;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Keeps the search of the organizations up to date (ADR 0050): whatever happened to an organization, its
 * words are indexed again in its account, or forgotten when it is no longer prospected.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class OrganizationSearchIndex
{
    public function __construct(
        private OrganizationRepository $organizations,
        private SearchProjection $prospectingSearch,
        private Table $prospectingTable,
    ) {
    }

    public function __invoke(OrganizationEvent $event): void
    {
        try {
            $this->index($this->organizations->get($event->organization));
        } catch (UnknownOrganization) {
            $this->prospectingSearch->forget($event->organization->value);
        }
    }

    /**
     * Indexes every organization again, after forgetting the whole projection: what repairs a drift.
     */
    public function rebuild(): int
    {
        $this->prospectingSearch->clear();
        $organizations = array_map(static fn (array $item): Organization => OrganizationDocument::organization($item['organization']), $this->prospectingTable->scan('ORGANIZATION#'));
        array_map($this->index(...), $organizations);

        return count($organizations);
    }

    private function index(Organization $organization): void
    {
        $this->prospectingSearch->index((string) $organization->id(), (string) $organization->accountId(), $organization->searchTerms());
    }
}
