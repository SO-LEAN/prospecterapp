<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class DashboardController.
 *
 * @Route(service="App\Controller\Web\DashboardController")
 */
class DashboardController
{
    use ControllerTrait;

    /**
     * @return HttpFoundation\Response
     *
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     */
    public function view()
    {
        return $this->render('page/dashboard/index.html.twig');
    }

    /**
     * @return HttpFoundation\Response
     */
    public function map(UserInterface $user)
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

    /**
     * @return HttpFoundation\Response
     */
    public function statistics(UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($user->getOrganizationId()), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/dashboard/_stats.html.twig', [
            'data' => $data,
        ]);
    }
}
