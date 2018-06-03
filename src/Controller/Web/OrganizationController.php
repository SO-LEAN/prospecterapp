<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Presenter\FindMyOwnOrganizationsPresenterImpl;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;

/**
 * Class ProspectController.
 *
 * @Route(service="App\Controller\Web\OrganizationController")
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
    public function create(HttpFoundation\Request $request, UserInterface $user)
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
     * @Route("organizations/{id}/update", name="organization_update")
     */
    public function update(HttpFoundation\Request $request, UserInterface $user)
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
     * @Route("/organizations/{id}/view", name="organization_view")
     */
    public function view($id, UserInterface $user)
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
    public function find($page = 1, HttpFoundation\Request $request, UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->findMyOwnOrganizations(
            new FindMyOwnOrganizationsRequest($page, $request->get('q', '')),
            new FindMyOwnOrganizationsPresenterImpl(), $user
        );

        return $this->render('page/organization/find.html.twig', ['data' => $data]);
    }
}
