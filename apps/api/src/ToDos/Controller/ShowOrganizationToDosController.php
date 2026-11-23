<?php

declare(strict_types=1);

namespace App\ToDos\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\ToDoReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('To-dos')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowOrganizationToDosController
{
    public function __construct(private ToDoReader $toDoReader, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/organizations/{id}/to-dos', name: 'api_organizations_to_dos', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The to-dos kept for the organization, for itself or for its contacts. Those still to do come first, the soonest due first. Those done follow')]
    #[OA\Response(response: 200, description: 'The to-dos', content: new Model(type: ToDoListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponses->of($this->toDoReader->ofOrganization($user->memberId(), $organization->id));
    }
}
