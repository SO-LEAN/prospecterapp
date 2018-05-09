<?php

namespace App\Controller;

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
 * Class ProspectController.
 *
 * @Route(service="App\Controller\OrganizationController")
 */
class OrganizationController
{
    use ControllerTrait;

    /**
     * @param HttpFoundation\Request $request
     * @param UserInterface          $user
     *
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("organizations/add", name="organization_create")
     */
    public function createOrganization(HttpFoundation\Request $request, UserInterface $user)
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }

    /**
     * @param HttpFoundation\Request $request
     * @param UserInterface          $user
     *
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("organizations/update/{id}", name="organization_update")
     */
    public function updateOrganization(HttpFoundation\Request $request, UserInterface $user)
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }

    /**
     * @param int           $id
     * @param UserInterface $user
     *
     * @return HttpFoundation\Response
     *
     * @Route("/organizations/view/{id}", name="organization_view")
     */
    public function viewOrganization($id, UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/organization/view.html.twig', ['data' => $data]);
    }

    /**
     * @param int                    $page
     * @param HttpFoundation\Request $request
     * @param UserInterface          $user
     *
     * @return HttpFoundation\Response
     *
     * @Route("/organizations/{page}", name="organization_find", requirements={"page"="\d+"})
     */
    public function findOrganization($page = 1, HttpFoundation\Request $request, UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->findOrganization(new FindOrganizationRequest($page, $request->get('q', '')), new FindOrganizationPresenterImpl(), $user);

        return $this->render('page/organization/find.html.twig', ['data' => $data]);
    }
}
