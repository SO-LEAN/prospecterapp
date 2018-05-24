<?php

namespace App\Controller\Web;

use App\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class ProspectController.
 *
 * @Route(service="App\Controller\Web\ProspectController")
 */
class ProspectController
{
    use ControllerTrait;

    /**
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     *
     * @Security("has_role('ROLE_PROSPECTOR')")
     */
    public function displayDashboard()
    {
        return $this->render('page/dashboard.html.twig');
    }

    /**
     * @Route("/prospects/add", name="prospect_create")
     */
    public function create()
    {
        return $this->render('page/prospect-add.html.twig');
    }
}