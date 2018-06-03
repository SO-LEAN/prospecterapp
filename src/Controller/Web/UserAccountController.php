<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Presenter\FindOrganizationPresenterImpl;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Solean\CleanProspecter\UseCase\FindOrganization\FindOrganizationRequest;

/**
 * Class UserController.
 *
 * @Route(service="App\Controller\Web\UserController")
 */
class UserAccountController
{
    use ControllerTrait;

    /**
     * @param HttpFoundation\Request $request
     * @param UserInterface          $user
     *
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("user-account/update", name="user_account_update")
     */
    public function update(HttpFoundation\Request $request, UserInterface $user)
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }
}
