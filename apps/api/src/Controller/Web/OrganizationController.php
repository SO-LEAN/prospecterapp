<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Presenter\FindMyOwnOrganizationsPresenterImpl;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Traits\ControllerTrait;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class OrganizationController
{
    use ControllerTrait;

    #[Route('organizations/add', name: 'organization_create')]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        return $this->handleForm($request, $user);
    }

    #[Route('organizations/{id}/update', name: 'organization_update')]
    public function update(Request $request, #[CurrentUser] User $user): Response
    {
        return $this->handleForm($request, $user);
    }

    #[Route('/organizations/{id}/view', name: 'organization_view')]
    public function view(int $id, #[CurrentUser] User $user): Response
    {
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        return $this->render('page/organization/view.html.twig', ['data' => $data]);
    }

    #[Route('/organizations/{page}', name: 'organization_find', requirements: ['page' => '\d+'])]
    public function find(Request $request, #[CurrentUser] User $user, int $page = 1): Response
    {
        $data = $this->getUseCases()->findMyOwnOrganizations(
            new FindMyOwnOrganizationsRequest($page, $request->query->getString('q')),
            new FindMyOwnOrganizationsPresenterImpl(),
            $user
        );

        return $this->render('page/organization/find.html.twig', ['data' => $data]);
    }
}
