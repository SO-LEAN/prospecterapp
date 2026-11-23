<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\Resource\TagsResource;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\TakeOffTag\TakeOffTag;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class TakeOrganizationTagOffController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private TagsReader $tagsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/tags/{tag}', name: 'api_organizations_take_off_tag', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Delete(summary: 'Take a tag off the organization. The tag is named without its `#`')]
    #[OA\Response(response: 200, description: 'The organization\'s tags now', content: new Model(type: TagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, an organization of another account, or a tag the organization does not have (`tagAbsent`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, string $tag, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new TakeOffTag(memberId: $user->memberId(), holder: TagRoutes::Organization->value, holderId: $organization->id, tag: $tag, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->tagsReader->read($user->memberId(), $organization->id));
    }
}
