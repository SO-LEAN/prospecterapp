<?php

namespace App\Service;

use App\Presenter;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PresenterFactory.
 */
class PresenterFactory
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * @param RouterInterface $router
     * @param ImageService    $imageService
     */
    public function __construct(RouterInterface $router, ImageService $imageService)
    {
        $this->router = $router;
        $this->imageService = $imageService;
    }

    /**
     * @return Presenter\FindOrganizationForDashboardMapPresenter
     */
    public function createFindOrganizationForDashBoardMapPresenter(): Presenter\FindOrganizationForDashboardMapPresenter
    {
        return new Presenter\FindOrganizationForDashboardMapPresenter($this->router, $this->imageService);
    }
}
