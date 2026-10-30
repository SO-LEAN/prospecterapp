<?php

namespace App\Contacts\Controller;

use App\Contacts\ContactReader;
use App\Contacts\Resource\ContactResource;
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
final class ShowContactController extends AbstractController
{
    public function __construct(private readonly ContactReader $contactReader)
    {
    }

    #[Route('/api/contacts/{id}', name: 'api_contacts_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Consult a person my organization keeps, in clear or masked')]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->contactReader->read($user->memberId(), $id));
    }
}
