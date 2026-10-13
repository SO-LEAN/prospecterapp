<?php

namespace App\Controller\Api;

use App\Api\Input\AccountInput;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use App\Presenter\UpdateMyAccountInformationPresenterImpl;
use App\Service\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/me', name: 'api_me_')]
#[OA\Tag('My account')]
#[OA\Response(response: 401, description: 'Missing or invalid access token')]
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $accountPresenter,
    ) {
    }

    #[Route('', name: 'show', methods: ['GET'])]
    #[OA\Get(summary: 'Get my account and my organization')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->read($user));
    }

    #[Route('', name: 'replace', methods: ['PUT'])]
    #[OA\Put(summary: 'Replace my account information (a null password keeps the current one; changing the user name ends the session)')]
    #[OA\Response(response: 200, description: 'My updated account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 422, description: 'Invalid account', content: new Model(type: ProblemResource::class))]
    public function replace(#[MapRequestPayload] AccountInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->update(new UpdateMyAccountInformationRequest(
            userName: $input->userName,
            password: $input->password,
            firstName: $input->firstName,
            lastName: $input->lastName,
            picture: null,
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            organizationCorporateName: $input->organizationCorporateName,
            organizationForm: $input->organizationForm,
            organizationLogo: null,
        ), $user);

        return $this->json($this->read($user));
    }

    #[Route('/picture', name: 'upload_picture', methods: ['POST'])]
    #[OA\Post(summary: 'Upload my picture (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'My account with its new picture', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function uploadPicture(#[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        $this->update($this->currentAccountRequest($user, picture: new UploadedFileInfo($file)), $user);

        return $this->json($this->read($user));
    }

    #[Route('/organization-logo', name: 'upload_organization_logo', methods: ['POST'])]
    #[OA\Post(summary: 'Upload the logo of my organization (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'My account with the new logo of my organization', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function uploadOrganizationLogo(#[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        $this->update($this->currentAccountRequest($user, organizationLogo: new UploadedFileInfo($file)), $user);

        return $this->json($this->read($user));
    }

    private function read(User $user): AccountResource
    {
        return $this->useCases->getMyAccountInformation(new GetMyAccountInformationRequest(), $this->accountPresenter, $user);
    }

    private function update(UpdateMyAccountInformationRequest $request, User $user): void
    {
        $this->useCases->updateMyAccountInformation($request, new UpdateMyAccountInformationPresenterImpl(), $user);
    }

    private function currentAccountRequest(User $user, ?UploadedFileInfo $picture = null, ?UploadedFileInfo $organizationLogo = null): UpdateMyAccountInformationRequest
    {
        $account = $this->read($user);

        return new UpdateMyAccountInformationRequest(
            userName: $account->userName,
            password: null,
            firstName: $account->firstName,
            lastName: $account->lastName,
            picture: $picture,
            phoneNumber: $account->phoneNumber,
            email: $account->email,
            language: $account->language,
            organizationCorporateName: $account->organization->corporateName,
            organizationForm: $account->organization->form,
            organizationLogo: $organizationLogo,
        );
    }
}
