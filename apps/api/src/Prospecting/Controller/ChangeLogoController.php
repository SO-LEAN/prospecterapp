<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Images;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\UploadedFileInfo;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Organization\ChangeLogo\ChangeLogo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ChangeLogoController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/logo', name: 'api_organizations_change_logo', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Post(summary: 'Send a new logo (multipart, field "file"). It is stored as a new file')]
    #[OA\Response(response: 200, description: 'The organization with its new logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that my account neither owns nor prospects', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid image', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapUploadedFile([new Assert\Image(maxSize: Images::LARGEST_BYTES, mimeTypes: Images::TYPES)])] UploadedFile $file, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeLogo(prospectorId: $user->memberId(), id: $organization->id, logo: new UploadedFileInfo($file), executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->organizationReader->read($organization->id));
    }
}
