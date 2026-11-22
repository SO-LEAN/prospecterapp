<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ProposedOrganizationReader;
use App\Contacts\Resource\ProposedOrganizationResource;
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
final class ShowProposedOrganizationController extends AbstractController
{
    public function __construct(private readonly ProposedOrganizationReader $proposedOrganizationReader)
    {
    }

    #[Route('/api/contacts/{id}/proposed-organization', name: 'api_contacts_proposed_organization', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The organization proposed to attach a contact to: the one of my account whose email has the domain of theirs, none for a public mailbox (ADR 0146)')]
    #[OA\Response(response: 200, description: 'The organization proposed, or none', content: new Model(type: ProposedOrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->proposedOrganizationReader->read($user->memberId(), $id));
    }
}
