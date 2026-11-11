<?php

namespace App\Dashboard\Presenter;

use App\Dashboard\Resource\DashboardStatsResource;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFiguresOutput;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;

/**
 * The figures of my organization for its dashboard. Built for one call, it keeps the resource (ADR 0085).
 */
final class FiguresPresenter implements ConsultFiguresOutput
{
    public private(set) DashboardStatsResource $resource;

    public function present(FiguresResponse $figures): void
    {
        $this->resource = new DashboardStatsResource(
            activeOrganizations: $figures->activeOrganizations,
            locatedOrganizations: $figures->locatedOrganizations,
            contacts: $figures->contacts,
            exchanges: $figures->exchanges,
            exchangesThisMonth: $figures->exchangesThisMonth,
        );
    }
}
