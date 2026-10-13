<?php

namespace App\Service;

use App\Presenter\FindMyOwnOrganizationsForDashboardMapPresenter;
use Symfony\Component\Routing\RouterInterface;

class PresenterFactory
{
    public function __construct(private readonly RouterInterface $router, private readonly ImageService $imageService)
    {
    }

    public function createFindMyOwnOrganizationsForDashBoardMapPresenter(): FindMyOwnOrganizationsForDashboardMapPresenter
    {
        return new FindMyOwnOrganizationsForDashboardMapPresenter($this->router, $this->imageService);
    }
}
