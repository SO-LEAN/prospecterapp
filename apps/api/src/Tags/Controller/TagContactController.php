<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\ContactTagsReader;
use App\Tags\Input\TagsInput;
use App\Tags\Resource\ContactTagsResource;
use App\Tags\TagRoutes;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\PutTags\PutTags;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class TagContactController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactTagsReader $contactTagsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/contacts/{id}/tags', name: 'api_contacts_tag', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Put tags on the contact. A tag it already carries is kept once. Every prospector may do it, with or without the right to manage personal data')]
    #[OA\Response(response: 200, description: 'The contact\'s tags now', content: new Model(type: ContactTagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Not a contact of my account (`unknownContact`)', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, text that makes no tag (`invalidTag`), or an automatic tag (`automaticTag`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] TagsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new PutTags(memberId: $user->memberId(), holder: TagRoutes::Contact->value, holderId: $id, tags: $input->tags, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->contactTagsReader->read($user->memberId(), $id));
    }
}
