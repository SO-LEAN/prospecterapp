<?php

namespace App\ToDos\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\ToDos\ToDoVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\EraseToDo\EraseToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class EraseToDoController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/to-dos/{id}', name: 'api_to_dos_erase', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(ToDoVoter::ACCESS, subject: 'toDo')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Delete(summary: 'Erase a to-do')]
    #[OA\Response(response: 204, description: 'Erased')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown to-do, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ToDoInList $toDo, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(new EraseToDo($user->memberId(), $toDo->id, $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
