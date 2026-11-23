<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\Resource\TagsResource;
use App\Tags\TagsReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowOrganizationTagsController
{
    public function __construct(private TagsReader $tagsReader, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/organizations/{id}/tags', name: 'api_organizations_tags', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Get(summary: 'Get the tags put by hand on the organization')]
    #[OA\Response(response: 200, description: 'The organization\'s tags', content: new Model(type: TagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or an organization of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponses->of($this->tagsReader->read($user->memberId(), $organization->id));
    }
}
