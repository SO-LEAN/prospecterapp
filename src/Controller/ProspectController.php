<?php

namespace App\Controller;

use App\Entity\User;
use App\Presenter\GetOrganizationPresenter;
use App\Traits\ControllerTrait;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * Class ProspectController.
 *
 * @Security("has_role('ROLE_PROSPECTOR')")
 * @Route(service="App\Controller\ProspectController")
 */
class ProspectController
{
    use ControllerTrait;

    /**
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     */
    public function displayDashboard()
    {
        return $this->render('page/dashboard.html.twig');
    }

    /**
     * @Route("/prospect/add", name="prospect_create")
     */
    public function createProspect()
    {
        return $this->render('page/prospect-add.html.twig');
    }

    /**
     * @param HttpFoundation\Request $request
     * @param UserInterface $user
     *
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("organization/add", name="organization_create")
     */
    public function createOrganization(HttpFoundation\Request $request, UserInterface $user)
    {
        /** @var User $user */
        return $this->handleForm($request, $user);
    }

    /**
     * @param int $id
     * @return HttpFoundation\Response
     * @param UserInterface $user
     * @Route("/organization/view/{id}", name="organization_view")
     */
    public function viewOrganization($id, UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenter(), $user);
        return $this->render('page/organization-view.html.twig', $data);
    }

}
