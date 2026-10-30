<?php

namespace App\Contacts\Controller;

use App\Contacts\OrganizationsOfContacts;
use App\Contacts\Resource\OrganizationsOfContactResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationsOfContactController extends AbstractController
{
    public function __construct(private readonly OrganizationsOfContacts $organizationsOfContacts)
    {
    }

    #[Route('/api/contacts/{id}/organizations', name: 'api_contacts_organizations', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'The organizations of my organization the contact works for')]
    #[OA\Response(response: 200, description: 'The organizations of the contact', content: new Model(type: OrganizationsOfContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json(new OrganizationsOfContactResource($this->organizationsOfContacts->read($user->memberId(), [$id])[$id] ?? []));
    }
}
