<?php

declare(strict_types=1);

namespace App\Me\Controller;

use App\Me\MyAccountComposition;
use App\Me\Resource\AccountResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Images;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\Account\ChangeMyOrganizationLogo\ChangeMyOrganizationLogo;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final readonly class ChangeMyOrganizationLogoController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountComposition $myAccountComposition,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/organization/logo', name: 'api_me_change_organization_logo', methods: ['POST'])]
    #[OA\Post(summary: 'Send my organization\'s new logo (multipart, field "file")')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapUploadedFile([new Assert\Image(maxSize: Images::LARGEST_BYTES, mimeTypes: Images::TYPES)])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeMyOrganizationLogo($user->memberId(), new UploadedFileInfo($file), $this->clock->now()));

        return $this->jsonResponses->of($this->myAccountComposition->read($user->memberId()));
    }
}
