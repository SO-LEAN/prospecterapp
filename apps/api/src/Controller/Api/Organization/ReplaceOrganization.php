<?php

namespace App\Controller\Api\Organization;

use App\Api\Input\OrganizationInput;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationResourcePresenter;
use App\Presenter\UpdateOrganizationPresenterImpl;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Organizations')]
final class ReplaceOrganization extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations/{id}', name: 'api_organizations_replace', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    #[OA\Put(summary: 'Replace an organization (fields left out are cleared, the logo is kept)')]
    #[OA\Response(response: 200, description: 'The updated organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is not prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid organization', content: new Model(type: ProblemResource::class))]
    public function __invoke(int $id, #[MapRequestPayload] OrganizationInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->useCases->updateOrganization(new UpdateOrganizationRequest(
            id: $id,
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            corporateName: $input->corporateName,
            form: $input->form,
            type: $input->type,
            address: $input->address?->toAddress(),
            observations: $input->observations,
            logo: null,
            holdBy: $input->holdBy,
        ), new UpdateOrganizationPresenterImpl(), $user);

        return $this->json($this->useCases->getOrganization(new GetOrganizationRequest($id), $this->presenter, $user));
    }
}
