<?php

namespace App\Controller;

use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;

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
     * @Route("organization/add", name="organization_create")
     */
    public function createOrganization(HttpFoundation\Request $request, UserInterface $user)
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }

    /**
     * @param int $id
     *
     * @return HttpFoundation\Response
     *
     * @param UserInterface $user
     * @Route("/organization/view/{id}", name="organization_view")
     */
    public function viewOrganization($id, UserInterface $user)
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/organization-view.html.twig', ['data' => $data]);
    }
}
