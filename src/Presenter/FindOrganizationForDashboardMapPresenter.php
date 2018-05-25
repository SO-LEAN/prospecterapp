<?php

namespace App\Presenter;

use Generator;
use App\Service\ImageService;
use Symfony\Component\Routing\RouterInterface;
use Solean\CleanProspecter\UseCase\FindOrganization\FindOrganizationResponse;
use Solean\CleanProspecter\UseCase\FindOrganization\FindOrganizationPresenter;

class FindOrganizationForDashboardMapPresenter implements FindOrganizationPresenter
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
     * @param FindOrganizationResponse $response
     *
     * @return array
     */
    public function present(FindOrganizationResponse $response): array
    {
        return iterator_to_array($this->organizationsToArray($response));
    }

    /**
     * @param FindOrganizationResponse $response
     *
     * @return Generator
     */
    private function organizationsToArray(FindOrganizationResponse $response): Generator
    {
        foreach ($response->getOrganizations() as $organization) {
            $hasCoordinates = null !== $organization->getLatitude() && null !== $organization->getLongitude();
            yield [
                'link' => $this->router->generate('organization_view', ['id' => $organization->getId()]),
                'fullName' => $organization->getFullName(),
                'logo' => $organization->getLogo() ? $this->imageService->buildOperationUrl($organization->getLogo(), ImageService::OPERATION_RESIZING, [32, 0]) : null,
            ] + ($hasCoordinates ? ['coordinates' => ['latitude' => $organization->getLatitude(), 'longitude' => $organization->getLongitude()]] : []);
        }
    }
}
