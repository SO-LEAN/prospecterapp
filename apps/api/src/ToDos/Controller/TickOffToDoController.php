<?php

declare(strict_types=1);

namespace App\ToDos\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoReader;
use App\ToDos\ToDoVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ToDo\TickOffToDo\TickOffToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class TickOffToDoController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ToDoReader $toDoReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/to-dos/{id}/done', name: 'api_to_dos_tick_off', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ToDoVoter::ACCESS, subject: 'toDo')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Tick a to-do off, as done now')]
    #[OA\Response(response: 200, description: 'The to-do, done', content: new Model(type: ToDoResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown to-do, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Already done', content: new Model(type: ProblemResource::class))]
    public function __invoke(ToDoInList $toDo, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new TickOffToDo($user->memberId(), $toDo->id, $this->clock->now()));

        return $this->jsonResponses->of($this->toDoReader->read($user->memberId(), $toDo->id));
    }
}
