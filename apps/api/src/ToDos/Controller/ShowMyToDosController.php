<?php

namespace App\ToDos\Controller;

use App\Security\User;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\ToDoReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowMyToDosController extends AbstractController
{
    public function __construct(private readonly ToDoReader $toDoReader)
    {
    }

    #[Route('/api/me/to-dos', name: 'api_me_to_dos', methods: ['GET'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The to-dos the signed-in member holds that are still to do, the soonest due first')]
    #[OA\Response(response: 200, description: 'My to-dos', content: new Model(type: ToDoListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->toDoReader->mine($user->memberId()));
    }
}
