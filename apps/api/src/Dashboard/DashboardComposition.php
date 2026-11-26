<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Dashboard\Presenter\FiguresPresenter;
use App\Dashboard\Resource\DashboardResource;
use App\Dashboard\Resource\DashboardStatsResource;
use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\KeptAccount;
use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFigures;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes a member's dashboard, a screen of three views (ADR 0217): their organization's name, which Accounts keeps,
 * the figures that Reporting keeps at the time of the read, and the organizations located on the map. A Prospecting
 * search finds these organizations, 1,000 at most. Each context answers its own query, side by side.
 */
final readonly class DashboardComposition
{
    private const int MAX_MARKERS = 1000;

    public function __construct(
        private QueryHandler $consultMyAccount,
        private QueryHandler $consultFigures,
        private QueryHandler $searchProspectedOrganizations,
        private ImageResourceFactory $imageResourceFactory,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $memberId): DashboardResource
    {
        $myAccount = new KeptAccount();
        $this->consultMyAccount->__invoke(new ConsultMyAccount($memberId), $myAccount);

        return new DashboardResource(
            organizationName: (string) $myAccount->account->organizationCorporateName,
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
