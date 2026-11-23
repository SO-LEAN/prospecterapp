<?php

declare(strict_types=1);

namespace App\ToDos\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Input\ToDoInput;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteOrganizationToDo\NoteOrganizationToDo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class NoteOrganizationToDoController
{
    public function __construct(
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private ToDoReader $toDoReader,
        private JsonResponses $jsonResponses,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/organizations/{id}/to-dos', name: 'api_organizations_note_to_do', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Log a to-do for the organization itself')]
    #[OA\Response(response: 201, description: 'The new to-do', content: new Model(type: ToDoResource::class), headers: [new OA\Header(header: 'Location', description: 'The new to-do', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid to-do: no text, a missing or invalid due day, or an invalid hour', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] ToDoInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $toDo = $this->identities->next();
        $this->commandBus->dispatch(new NoteOrganizationToDo(
            prospectorId: $user->memberId(),
            id: $toDo,
            organizationId: $organization->id,
            text: (string) $input->text,
            dueOn: (string) $input->dueOn,
            dueAt: $input->dueAt,
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->toDoReader->read($user->memberId(), $toDo), Response::HTTP_CREATED, ['Location' => $this->urlGenerator->generate('api_to_dos_show', ['id' => $toDo])]);
    }
}
