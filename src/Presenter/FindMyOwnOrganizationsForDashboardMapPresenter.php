<?php

namespace App\Presenter;

use App\Service\ImageService;
use Generator;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Symfony\Component\Routing\RouterInterface;

class FindMyOwnOrganizationsForDashboardMapPresenter implements FindMyOwnOrganizationsPresenter
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ImageService
     */
    private $imageService;

    public function __construct(RouterInterface $router, ImageService $imageService)
    {
        $this->router = $router;
        $this->imageService = $imageService;
    }

    public function present(FindMyOwnOrganizationsResponse $response): array
    {
        return iterator_to_array($this->organizationsToArray($response));
    }

    private function organizationsToArray(FindMyOwnOrganizationsResponse $response): Generator
    {
        foreach ($response->getOrganizations() as $organization) {
            $hasCoordinates = null !== $organization->getLatitude() && null !== $organization->getLongitude();
            yield [
                'link' => $this->router->generate('organization_view', ['id' => $organization->getId()], RouterInterface::ABSOLUTE_URL),
                'fullName' => $organization->getFullName(),
                'logo' => $organization->getLogo() ? $this->imageService->buildOperationUrl($organization->getLogo(), ImageService::OPERATION_RESIZING, [32, 0]) : null,
            ] + ($hasCoordinates ? ['coordinates' => ['latitude' => $organization->getLatitude(), 'longitude' => $organization->getLongitude()]] : []);
        }
    }
}
