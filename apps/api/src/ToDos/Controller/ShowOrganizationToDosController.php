<?php

namespace App\ToDos\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\ToDoReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationToDosController extends AbstractController
{
    public function __construct(private readonly ToDoReader $toDoReader)
    {
    }

    #[Route('/api/organizations/{id}/to-dos', name: 'api_organizations_to_dos', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The to-dos kept for the organization, for it or for its contacts, those to do the soonest due first, then those done')]
    #[OA\Response(response: 200, description: 'The to-dos', content: new Model(type: ToDoListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->toDoReader->ofOrganization($user->memberId(), $organization->id));
    }
}
