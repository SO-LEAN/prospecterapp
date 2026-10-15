<?php

namespace App\Controller\Api\Organization;

use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\ConsultOrganizationRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Organizations')]
final class ShowOrganization extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations/{id}', name: 'api_organizations_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Consult an organization: mine or one my organization prospects')]
    #[OA\Response(response: 200, description: 'The organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is not prospected by mine', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->consultOrganization(new ConsultOrganizationRequest(new OrganizationId($id)), $this->presenter, $user));
    }
}
