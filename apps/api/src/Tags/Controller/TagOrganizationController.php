<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\Input\TagsInput;
use App\Tags\Resource\TagsResource;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class TagOrganizationController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private TagsReader $tagsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/tags', name: 'api_organizations_tag', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Post(summary: 'Put tags on the organization. A tag it already carries is kept once')]
    #[OA\Response(response: 200, description: 'The organization\'s tags now', content: new Model(type: TagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or an organization of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, text that makes no tag (`invalidTag`), or an automatic tag (`automaticTag`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] TagsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new PutTags(memberId: $user->memberId(), holder: TagRoutes::Organization->value, holderId: $organization->id, tags: $input->tags, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->tagsReader->read($user->memberId(), $organization->id));
    }
}
