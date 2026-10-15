<?php

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\Shared\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ChangePersonPicture\ChangePersonPicture;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyPicture extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me/picture', name: 'api_me_change_picture', methods: ['POST'])]
    #[OA\Post(summary: 'Send my new picture (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapUploadedFile([new Assert\Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ChangePersonPicture($user->memberId(), $user->personId(), new UploadedFileInfo($file)));

        return $this->json($this->account->read($user->memberId()));
    }
}
