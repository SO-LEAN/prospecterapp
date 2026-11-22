<?php

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\Tags\ContactTagsReader;
use App\Tags\Input\TagsInput;
use App\Tags\Resource\ContactTagsResource;
use App\Tags\TagRoutes;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
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
final class TagContactController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ContactTagsReader $contactTagsReader,
    ) {
    }

    #[Route('/api/contacts/{id}/tags', name: 'api_contacts_tag', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Put tags on the contact, a tag it carries already carried once; every prospector, with or without the right to manage personal data')]
    #[OA\Response(response: 200, description: 'Its tags now', content: new Model(type: ContactTagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No contact of my account (`unknownContact`)', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, one that makes no tag (`invalidTag`), or an automatic one (`automaticTag`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] TagsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new PutTags(memberId: $user->memberId(), holder: TagRoutes::Contact->value, holderId: $id, tags: $input->tags, executedAt: $this->clock->now()));

        return $this->json($this->contactTagsReader->read($user->memberId(), $id));
    }
}
