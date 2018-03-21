<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

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