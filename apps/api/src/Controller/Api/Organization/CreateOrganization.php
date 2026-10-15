<?php

namespace App\Controller\Api\Organization;

use App\Api\Input\OrganizationInput;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\CreateOrganizationPresenterImpl;
use App\Presenter\OrganizationResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Organizations')]
final class CreateOrganization extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations', name: 'api_organizations_create', methods: ['POST'])]
    #[OA\Post(summary: 'Create an organization owned by my organization')]
    #[OA\Response(response: 201, description: 'The created organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Invalid organization', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] OrganizationInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $created = $this->useCases->createOrganization(new CreateOrganizationRequest(
            ownedBy: $user->getOrganizationId(),
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            corporateName: $input->corporateName,
            type: $input->type,
            form: $input->form,
            address: $input->address?->toAddress(),
            observations: $input->observations,
            logo: null,
            holdBy: $input->holdBy,
        ), new CreateOrganizationPresenterImpl(), $user);

        $id = $created->id;

        return $this->json(
            $this->useCases->getOrganization(new GetOrganizationRequest($id), $this->presenter, $user),
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_organizations_show', ['id' => $id])],
        );
    }
}
