<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProspectController
 * @package App\Controller
 * @Security("has_role('ROLE_PROSPECTOR')")
 */
class ProspectController extends Controller
{
    /**
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     */
    public function displayDashboard()
    {
        return $this->render('page/dashboard.html.twig');
    }
    /**
     * @Route("/prospect/add", name="prospect_add")
     */
    public function addProspect()
    {
        return $this->render('page/prospect-add.html.twig');
    }
}