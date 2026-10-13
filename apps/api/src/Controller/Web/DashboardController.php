<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DashboardController
{
    use ControllerTrait;

    #[Route('/', name: 'index')]
    #[Route('/dashboard/view', name: 'dashboard_display')]
    public function view(): Response
    {
        return $this->render('page/dashboard/index.html.twig');
    }

    public function map(#[CurrentUser] User $user): Response
    {
        $data = $this->getUseCases()->findMyOwnOrganizations(
            new FindMyOwnOrganizationsRequest(1, '', 1000),
            $this->getPresenterFactory()->createFindMyOwnOrganizationsForDashBoardMapPresenter(),
            $user
        );

        return $this->render('page/dashboard/_map.html.twig', [
            'data' => $data,
        ]);
    }

    public function statistics(#[CurrentUser] User $user): Response
    {
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($user->getOrganizationId()), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/dashboard/_stats.html.twig', [
            'data' => $data,
        ]);
    }
}
