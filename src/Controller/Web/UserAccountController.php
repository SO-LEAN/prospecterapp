<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserController.
 *
 * @Route(service="App\Controller\Web\UserAccountController")
 */
class UserAccountController
{
    use ControllerTrait;

    /**
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("user-account/update", name="my_account_information_update")
     */
    public function update(HttpFoundation\Request $request, UserInterface $user)
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }
}
