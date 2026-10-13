<?php

namespace App\Controller\Api\Organization;

use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Presenter\OrganizationResourcePresenter;
use App\Presenter\UpdateOrganizationPresenterImpl;
use App\Service\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Tag('Organizations')]
final class UploadLogo extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations/{id}/logo', name: 'api_organizations_upload_logo', requirements: ['id' => '\\d+'], methods: ['POST'])]
    #[OA\Post(summary: 'Upload the logo of an organization (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'The organization with its new logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 404, description: 'Unknown organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function __invoke(int $id, #[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        /** @var GetOrganizationResponse $current */
        $current = $this->useCases->getOrganization(new GetOrganizationRequest($id), new GetOrganizationPresenterImpl(), $user);

        // the use case replaces every field: send back the current values with the new logo
        $this->useCases->updateOrganization(new UpdateOrganizationRequest(
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
        ), new UpdateOrganizationPresenterImpl(), $user);

        return $this->json($this->useCases->getOrganization(new GetOrganizationRequest($id), $this->presenter, $user));
    }
}
