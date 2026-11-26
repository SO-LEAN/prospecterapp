<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ProposedOrganizationPresenter;
use App\Contacts\Resource\ProposedOrganizationResource;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach\ProposeOrganizationToAttach;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowProposedOrganizationController
{
    public function __construct(private QueryHandler $proposeOrganizationToAttach, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/contacts/{id}/proposed-organization', name: 'api_contacts_proposed_organization', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'Get the organization proposed to attach a contact to. It is the organization of my account whose email has the same domain as the contact\'s. A public mailbox gets none (ADR 0146)')]
    #[OA\Response(response: 200, description: 'The organization proposed, or none', content: new Model(type: ProposedOrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The contact is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $presenter = new ProposedOrganizationPresenter();
        $this->proposeOrganizationToAttach->__invoke(new ProposeOrganizationToAttach($user->memberId(), $id), $presenter);

        return $this->jsonResponses->of($presenter->resource);
    }
}
