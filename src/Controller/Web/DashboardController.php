<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController
{
    use ControllerTrait;

    #[Route('/', name: 'index')]
    #[Route('/dashboard/view', name: 'dashboard_display')]
    public function view(): HttpFoundation\Response
    {
        return $this->render('page/dashboard/index.html.twig');
    }

    public function map(UserInterface $user): HttpFoundation\Response
    {
        /** @var User $user */
        $data = $this->getUseCases()->findMyOwnOrganizations(
            new FindMyOwnOrganizationsRequest(1, '', 1000),
            $this->getPresenterFactory()->createFindMyOwnOrganizationsForDashBoardMapPresenter(),
            $user
        );

        return $this->render('page/dashboard/_map.html.twig', [
            'data' => $data,
        ]);
    }

    public function statistics(UserInterface $user): HttpFoundation\Response
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($user->getOrganizationId()), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/dashboard/_stats.html.twig', [
            'data' => $data,
        ]);
    }
}
