<?php

namespace App\Controller\Api\Account;

use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use App\Service\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogoRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Tag('My account')]
final class ChangeMyOrganizationLogo extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/me/organization/logo', name: 'api_me_change_organization_logo', methods: ['POST'])]
    #[OA\Post(summary: 'Send the new logo of my organization (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->changeMyOrganizationLogo(new ChangeMyOrganizationLogoRequest(new UploadedFileInfo($file)), $this->presenter, $user));
    }
}
