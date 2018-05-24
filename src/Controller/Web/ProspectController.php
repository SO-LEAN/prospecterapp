<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use App\Presenter\GetOrganizationPresenterImpl;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;

/**
 * Class ProspectController.
 *
 * @Route(service="App\Controller\Web\ProspectController")
 */
class ProspectController
{
    use ControllerTrait;

    /**
     * @param UserInterface $user
     *
     * @return HttpFoundation\Response
     *
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     * @Security("has_role('ROLE_PROSPECTOR')")
     */
    public function displayDashboard(UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($user->getOrganizationId()), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/dashboard.html.twig', ['data' => $data]);
    }

    /**
     * @Route("/prospects/add", name="prospect_create")
     */
    public function create()
    {
        return $this->render('page/prospect-add.html.twig');
    }
}
