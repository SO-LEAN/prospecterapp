<?php

namespace App\Controller\Web;

use App\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class ProspectController.
 *
 * @Route(service="App\Controller\Web\ProspectController")
 */
class ProspectController
{
    use ControllerTrait;

    /**
     * @Route("/prospects/add", name="prospect_create")
     */
    public function create()
    {
        return $this->render('page/prospect-add.html.twig');
    }
}
