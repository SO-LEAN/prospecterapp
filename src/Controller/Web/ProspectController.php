<?php

namespace App\Controller\Web;

use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProspectController
{
    use ControllerTrait;

    #[Route('/prospects/add', name: 'prospect_create')]
    public function create(): Response
    {
        return $this->render('page/prospect-add.html.twig');
    }
}
