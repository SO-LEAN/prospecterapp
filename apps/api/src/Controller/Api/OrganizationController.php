<?php

namespace App\Controller\Api;

use App\Api\Input\OrganizationInput;
use App\Api\Input\OrganizationListQuery;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\CreateOrganizationPresenterImpl;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Presenter\OrganizationPagePresenter;
use App\Presenter\OrganizationResourcePresenter;
use App\Presenter\RemoveOrganizationLogoPresenterImpl;
use App\Presenter\UpdateOrganizationPresenterImpl;
use App\Service\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoRequest;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/organizations', name: 'api_organizations_')]
#[OA\Tag('Organizations')]
#[OA\Response(response: 401, description: 'Missing or invalid access token')]
final class OrganizationController extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $organizationPresenter,
        private readonly OrganizationPagePresenter $pagePresenter,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Find the organizations owned by my organization, by name or email')]
    #[OA\Response(response: 200, description: 'A page of organizations, most recent first', content: new Model(type: OrganizationPageResource::class))]
    public function list(#[CurrentUser] User $user, #[MapQueryString] OrganizationListQuery $query = new OrganizationListQuery()): JsonResponse
    {
        return $this->json($this->useCases->findMyOwnOrganizations(new FindMyOwnOrganizationsRequest($query->page, $query->q), $this->pagePresenter, $user));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Create an organization owned by my organization')]
    #[OA\Response(response: 201, description: 'The created organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 422, description: 'Invalid organization', content: new Model(type: ProblemResource::class))]
    public function create(#[MapRequestPayload] OrganizationInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $created = $this->useCases->createOrganization(new CreateOrganizationRequest(
            ownedBy: $user->getOrganizationId(),
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            corporateName: $input->corporateName,
            type: $input->type,
            form: $input->form,
            street: $input->street,
            postalCode: $input->postalCode,
            city: $input->city,
            country: $input->country,
            observations: $input->observations,
            logo: null,
            holdBy: $input->holdBy,
        ), new CreateOrganizationPresenterImpl(), $user);

        $id = (int) $created->getId();

        return $this->json($this->read($id, $user), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_organizations_show', ['id' => $id]),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(summary: 'Get an organization')]
    #[OA\Response(response: 200, description: 'The organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization', content: new Model(type: ProblemResource::class))]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->read($id, $user));
    }

    #[Route('/{id}', name: 'replace', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Put(summary: 'Replace an organization (fields left out are cleared, the logo is kept)')]
    #[OA\Response(response: 200, description: 'The updated organization', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid organization', content: new Model(type: ProblemResource::class))]
    public function replace(int $id, #[MapRequestPayload] OrganizationInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->update(new UpdateOrganizationRequest(
            id: $id,
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            corporateName: $input->corporateName,
            form: $input->form,
            type: $input->type,
            street: $input->street,
            postalCode: $input->postalCode,
            city: $input->city,
            country: $input->country,
            observations: $input->observations,
            logo: null,
            holdBy: $input->holdBy,
        ), $user);

        return $this->json($this->read($id, $user));
    }

    #[Route('/{id}/logo', name: 'upload_logo', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Post(summary: 'Upload the logo of an organization (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'The organization with its new logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function uploadLogo(
        int $id,
        #[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])]
        UploadedFile $file,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        /** @var GetOrganizationResponse $current */
        $current = $this->useCases->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        $this->update(new UpdateOrganizationRequest(
            id: $id,
            phoneNumber: $current->getPhoneNumber(),
            email: $current->getEmail(),
            language: $current->getLanguage(),
            corporateName: $current->getCorporateName(),
            form: $current->getForm(),
            type: $current->getType(),
            street: $current->getStreet(),
            postalCode: $current->getPostalCode(),
            city: $current->getCity(),
            country: $current->getCountry(),
            observations: $current->getObservations(),
            logo: new UploadedFileInfo($file),
            holdBy: $current->getHoldBy(),
        ), $user);

        return $this->json($this->read($id, $user));
    }

    #[Route('/{id}/logo', name: 'delete_logo', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove the logo of an organization')]
    #[OA\Response(response: 200, description: 'The organization without logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization', content: new Model(type: ProblemResource::class))]
    public function deleteLogo(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->useCases->removeOrganizationLogo(new RemoveOrganizationLogoRequest($id), new RemoveOrganizationLogoPresenterImpl(), $user);

        return $this->json($this->read($id, $user));
    }

    private function read(int $id, User $user): OrganizationResource
    {
        return $this->useCases->getOrganization(new GetOrganizationRequest($id), $this->organizationPresenter, $user);
    }

    private function update(UpdateOrganizationRequest $request, User $user): void
    {
        $this->useCases->updateOrganization($request, new UpdateOrganizationPresenterImpl(), $user);
    }
}
