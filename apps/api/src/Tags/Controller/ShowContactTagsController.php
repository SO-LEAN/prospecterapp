<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\Resource\ProblemResource;
use App\Tags\ContactTagsReader;
use App\Tags\Resource\ContactTagsResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowContactTagsController extends AbstractController
{
    public function __construct(private readonly ContactTagsReader $contactTagsReader)
    {
    }

    #[Route('/api/contacts/{id}/tags', name: 'api_contacts_tags', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Get the contact\'s tags: its own tags, the tags it inherits from its organizations, and the automatic tags for how it is reached')]
    #[OA\Response(response: 200, description: 'The contact\'s tags', content: new Model(type: ContactTagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Not a contact of my account (`unknownContact`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->contactTagsReader->read($user->memberId(), $id));
    }
}
