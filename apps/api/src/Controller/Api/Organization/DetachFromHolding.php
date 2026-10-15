<?php

namespace App\Controller\Api\Organization;

use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\DetachFromHoldingRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DetachFromHolding extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations/{id}/holding', name: 'api_organizations_detach_from_holding', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Detach the organization from its holding')]
    #[OA\Response(response: 200, description: 'The organization without holding', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The organization has no holding (no detachFromHolding link)', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->detachFromHolding(new DetachFromHoldingRequest($user->getUserId(), new OrganizationId($id)), $this->presenter));
    }
}
