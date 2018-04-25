<?php

namespace App\Controller;

use App\Entity\User;
use App\Traits\ControllerTrait;
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
    public function addProspect()
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
        return $this->handleForm([], $request, $user);
    }

    /**
     * @Route("/organization/view/{id}", name="organization_view")
     */
    public function viewOrganization($id)
    {
        return $this->render('page/prospect-add.html.twig');
    }

}
