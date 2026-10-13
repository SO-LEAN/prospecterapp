<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Presenter\FindMyOwnOrganizationsPresenterImpl;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class OrganizationController
{
    use ControllerTrait;

    #[Route('organizations/add', name: 'organization_create')]
    public function create(HttpFoundation\Request $request, UserInterface $user): HttpFoundation\Response
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }

    #[Route('organizations/{id}/update', name: 'organization_update')]
    public function update(HttpFoundation\Request $request, UserInterface $user): HttpFoundation\Response
    {
        /* @var User $user */
        return $this->handleForm($request, $user);
    }

    #[Route('/organizations/{id}/view', name: 'organization_view')]
    public function view(int $id, UserInterface $user): HttpFoundation\Response
    {
        /** @var User $user */
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/organization/view.html.twig', ['data' => $data]);
    }

    #[Route('/organizations/{page}', name: 'organization_find', requirements: ['page' => '\d+'])]
    public function find(HttpFoundation\Request $request, UserInterface $user, int $page = 1): HttpFoundation\Response
    {
        /** @var User $user */
        $data = $this->getUseCases()->findMyOwnOrganizations(
            new FindMyOwnOrganizationsRequest($page, $request->query->getString('q')),
            new FindMyOwnOrganizationsPresenterImpl(), $user
        );

        return $this->render('page/organization/find.html.twig', ['data' => $data]);
    }
}
