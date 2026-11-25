<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\ContactTagsReader;
use App\Tags\Resource\ContactTagsResource;
use App\Tags\TagHolder;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\Tagging\TakeOffTag\TakeOffTag;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class TakeContactTagOffController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactTagsReader $contactTagsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/contacts/{id}/tags/{tag}', name: 'api_contacts_take_off_tag', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Take a tag off the contact. The tag is named without its `#`')]
    #[OA\Response(response: 200, description: 'The contact\'s tags now', content: new Model(type: ContactTagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Not a contact of my account (`unknownContact`), or a tag the contact does not have (`tagAbsent`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, string $tag, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new TakeOffTag(memberId: $user->memberId(), holder: TagHolder::Contact->value, holderId: $id, tag: $tag, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->contactTagsReader->read($user->memberId(), $id));
    }
}
