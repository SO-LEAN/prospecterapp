<?php

namespace App\ToDos\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Input\ToDoInput;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoReader;
use App\ToDos\ToDoVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ReviseToDo\ReviseToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ReviseToDoController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ToDoReader $toDoReader,
    ) {
    }

    #[Route('/api/to-dos/{id}', name: 'api_to_dos_revise', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(ToDoVoter::ACCESS, subject: 'toDo')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Patch(summary: 'Change the text of a to-do and when it is due')]
    #[OA\Response(response: 200, description: 'The revised to-do', content: new Model(type: ToDoResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown to-do, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid to-do: without a text, due on no day or at no hour', content: new Model(type: ProblemResource::class))]
    public function __invoke(ToDoInList $toDo, #[MapRequestPayload] ToDoInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ReviseToDo(
            prospectorId: $user->memberId(),
            id: $toDo->id,
            text: (string) $input->text,
            dueOn: (string) $input->dueOn,
            dueAt: $input->dueAt,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->toDoReader->read($user->memberId(), $toDo->id));
    }
}
