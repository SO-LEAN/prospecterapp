<?php

namespace App\Connectors\Controller;

use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class CatchUpOrganizationGmailController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/organizations/{id}/gmail-catch-ups', name: 'api_organizations_catch_up_gmail', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Catch up the e-mails my mailbox of Gmail exchanged with the address of the organization itself over the 12 months before, the 200 newest: imported as the import of my e-mails imports them, asynchronously')]
    #[OA\Response(response: 202, description: 'The catch-up started: the e-mails arrive in the timeline of the organization')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'No right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account, or no connection to Google', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The import of my e-mails is not active, or the organization has no e-mail', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(CatchUpMailHistory::ofOrganization($user->memberId(), $organization->id, $this->clock->now()));

        return new Response(status: Response::HTTP_ACCEPTED);
    }
}
