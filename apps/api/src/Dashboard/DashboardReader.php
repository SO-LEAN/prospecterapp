<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Dashboard\Presenter\FiguresPresenter;
use App\Dashboard\Resource\DashboardResource;
use App\Dashboard\Resource\DashboardStatsResource;
use App\Me\MyAccountReader;
use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFigures;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * The dashboard of a member: the name of their organization, the figures Reporting keeps at the moment of the
 * reading, and the organizations located on the map, a search of Prospecting of 1 000 at most.
 */
final readonly class DashboardReader
{
    private const int MAX_MARKERS = 1000;

    public function __construct(
        private MyAccountReader $myAccountReader,
        private QueryHandler $consultFigures,
        private QueryHandler $searchProspectedOrganizations,
        private ImageResourceFactory $imageResourceFactory,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $memberId): DashboardResource
    {
        $myAccount = $this->myAccountReader->read($memberId);

        return new DashboardResource(
            organizationName: (string) $myAccount->organization->legalIdentity->corporateName,
            stats: $this->figuresOf($memberId),
            markers: $this->markersOf($memberId),
        );
    }

    private function figuresOf(string $memberId): DashboardStatsResource
    {
        $presenter = new FiguresPresenter();
        $this->consultFigures->__invoke(new ConsultFigures($memberId, $this->clock->now()), $presenter);

        return $presenter->resource;
    }

    /**
     * @return list<OrganizationSummaryResource>
     */
    private function markersOf(string $memberId): array
    {
        $presenter = new OrganizationPagePresenter($this->imageResourceFactory, $this->links);
        $this->searchProspectedOrganizations->__invoke(new SearchProspectedOrganizations($memberId, max: self::MAX_MARKERS), $presenter);

        return array_values(array_filter($presenter->resource->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location));
    }
}
