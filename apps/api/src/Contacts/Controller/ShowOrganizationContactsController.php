<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\OrganizationContactsReader;
use App\Contacts\Resource\OrganizationContactsResource;
use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Shows an organization's contacts. `OrganizationContactsReader` reads them (ADR 0041).
 */
#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationContactsController extends AbstractController
{
    public function __construct(private readonly OrganizationContactsReader $contacts)
    {
    }

    #[Route('/api/organizations/{id}/contacts', name: 'api_organizations_contacts', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Get(summary: 'List the organization\'s contacts, unmasked or masked')]
    #[OA\Response(response: 200, description: 'The contacts, in the order they were attached', content: new Model(type: OrganizationContactsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The organization is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->contacts->read($user->memberId(), $organization));
    }
}
