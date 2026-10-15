<?php

namespace App\Controller\Api\Organization;

use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationResourcePresenter;
use App\Presenter\RemoveOrganizationLogoPresenterImpl;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Organizations')]
final class DeleteLogo extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations/{id}/logo', name: 'api_organizations_delete_logo', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove the logo of an organization')]
    #[OA\Response(response: 200, description: 'The organization without logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is not prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'The organization has no logo', content: new Model(type: ProblemResource::class))]
    public function __invoke(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->useCases->removeOrganizationLogo(new RemoveOrganizationLogoRequest($id), new RemoveOrganizationLogoPresenterImpl(), $user);

        return $this->json($this->useCases->getOrganization(new GetOrganizationRequest($id), $this->presenter, $user));
    }
}
