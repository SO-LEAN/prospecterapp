<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use App\Presenter\GetOrganizationPresenterImpl;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;

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
     * @param UserInterface $user
     *
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
     * @param UserInterface $user
     *
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
