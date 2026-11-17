<?php

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\Tags\Resource\TagsResource;
use App\Tags\TagRoutes;
use App\Tags\TagsReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\TakeOffTag\TakeOffTag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final class TakeContactTagOffController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly TagsReader $tagsReader,
    ) {
    }

    #[Route('/api/contacts/{id}/tags/{tag}', name: 'api_contacts_take_off_tag', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Take a tag off the contact, named without its mark')]
    #[OA\Response(response: 200, description: 'Its tags now', content: new Model(type: TagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No contact of my account (`unknownContact`), or a tag it does not carry (`tagNotCarried`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, string $tag, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new TakeOffTag($user->memberId(), TagRoutes::Contact->value, $id, $tag, $this->clock->now()));

        return $this->json($this->tagsReader->read($user->memberId(), TagRoutes::Contact, $id));
    }
}
