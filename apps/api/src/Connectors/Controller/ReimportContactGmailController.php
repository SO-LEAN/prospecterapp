<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class ReimportContactGmailController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/contacts/{id}/gmail-reimports', name: 'api_contacts_reimport_gmail', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Reimport the e-mails my mailbox of Gmail exchanged with the contact over the 12 months before, the 200 newest: imported as the import of my e-mails imports them, asynchronously')]
    #[OA\Response(response: 202, description: 'The reimport started: the e-mails arrive in the timeline of the contact')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'No right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No connection to Google, or an unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The import of my e-mails is not active, or the contact has no e-mail', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(CatchUpMailHistory::ofContact($user->memberId(), $id, $this->clock->now()));

        return new Response(status: Response::HTTP_ACCEPTED);
    }
}
