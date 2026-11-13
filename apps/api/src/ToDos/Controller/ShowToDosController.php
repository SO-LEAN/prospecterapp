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
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowToDosController extends AbstractController
{
    public function __construct(private readonly ToDoReader $toDoReader)
    {
    }

    #[Route('/api/contacts/{id}/to-dos', name: 'api_contacts_to_dos', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The to-dos for the contact, those to do the soonest due first, then those done')]
    #[OA\Response(response: 200, description: 'The to-dos', content: new Model(type: ToDoListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->toDoReader->ofContact($user->memberId(), $id));
    }
}
