<?php

namespace App\Tags\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\Tags\Input\TagsInput;
use App\Tags\Resource\TagsResource;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final class TagOrganizationController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly TagsReader $tagsReader,
    ) {
    }

    #[Route('/api/organizations/{id}/tags', name: 'api_organizations_tag', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Post(summary: 'Put tags on the organization, a tag it carries already carried once')]
    #[OA\Response(response: 200, description: 'Its tags now', content: new Model(type: TagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, one that makes no tag (`invalidTag`), or an automatic one (`automaticTag`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] TagsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new PutTags($user->memberId(), TagRoutes::Organization->value, $organization->id, $input->tags, $this->clock->now()));

        return $this->json($this->tagsReader->read($user->memberId(), TagRoutes::Organization, $organization->id));
    }
}
