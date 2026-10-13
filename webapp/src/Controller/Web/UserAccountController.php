<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserAccountController
{
    use ControllerTrait;

    #[Route('user-account/update', name: 'my_account_information_update')]
    public function update(Request $request, #[CurrentUser] User $user): Response
    {
        return $this->handleForm($request, $user);
    }
}
