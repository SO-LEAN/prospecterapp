<?php

namespace App\Controller\Api\Account;

use App\Api\AccountReader;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Messenger\CommandBus;
use App\Service\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Account\ChangeMyPicture\ChangeMyPicture as ChangeMyPictureCommand;
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
        private readonly AccountReader $account,
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
        $this->commands->dispatch(new ChangeMyPictureCommand($user->getUserId(), new UploadedFileInfo($file)));

        return $this->json($this->account->read($user->getUserId()));
    }
}
