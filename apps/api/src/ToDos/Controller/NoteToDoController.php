<?php

namespace App\ToDos\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Input\ToDoInput;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteToDo\NoteToDo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class NoteToDoController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly ToDoReader $toDoReader,
    ) {
    }

    #[Route('/api/contacts/{id}/to-dos', name: 'api_contacts_note_to_do', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Note a to-do for the contact, kept for the organizations the contact is part of')]
    #[OA\Response(response: 201, description: 'The new to-do', content: new Model(type: ToDoResource::class), headers: [new OA\Header(header: 'Location', description: 'The new to-do', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid to-do: without a text, due on no day or at no hour', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] ToDoInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $toDo = $this->identities->next();
        $this->commandBus->dispatch(new NoteToDo(
            prospectorId: $user->memberId(),
            id: $toDo,
            personId: $id,
            text: (string) $input->text,
            dueOn: (string) $input->dueOn,
            dueAt: $input->dueAt,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->toDoReader->read($user->memberId(), $toDo), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_to_dos_show', ['id' => $toDo])]);
    }
}
