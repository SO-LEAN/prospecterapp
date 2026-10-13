<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAccountController
{
    use ControllerTrait;

    #[Route('user-account/update', name: 'my_account_information_update')]
    public function update(HttpFoundation\Request $request, UserInterface $user): HttpFoundation\Response
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }
}
